<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateTimeImmutable;

/**
 * Extrae señales de un comprobante para ayudar a la revisión humana.
 * Nunca modifica el estado del pedido ni aprueba un pago.
 */
final class ReceiptAiService
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config)
    {
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['receipt_ai_enabled'])
            && trim((string) ($this->config['openai_api_key'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $order
     * @param array{holder: string, alias: string, cbu: string} $bank
     * @return array<string, mixed>
     */
    public function prevalidate(
        string $filePath,
        string $mimeType,
        array $order,
        array $bank
    ): array {
        if (!$this->isConfigured()) {
            return [
                'status' => 'disabled',
                'risk_level' => null,
                'summary' => 'Prevalidación automática no configurada.',
                'model' => null,
                'result' => null,
            ];
        }
        if (!is_file($filePath)) {
            throw new \RuntimeException('No se encontró el comprobante para analizar.');
        }

        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new \RuntimeException('No se pudo leer el comprobante.');
        }

        $model = trim((string) ($this->config['receipt_ai_model'] ?? 'gpt-5.6-sol'));
        $document = $mimeType === 'application/pdf'
            ? [
                'type' => 'input_file',
                'filename' => 'comprobante.pdf',
                'file_data' => 'data:application/pdf;base64,' . base64_encode($contents),
            ]
            : [
                'type' => 'input_image',
                'image_url' => 'data:' . $mimeType . ';base64,' . base64_encode($contents),
                'detail' => 'high',
            ];

        $expected = [
            'pedido' => (string) ($order['public_number'] ?? ''),
            'importe_centavos' => (int) ($order['total_cents'] ?? 0),
            'moneda' => 'ARS',
            'creado' => (string) ($order['created_at'] ?? ''),
            'titular' => $bank['holder'],
            'alias' => $bank['alias'],
            'cbu_cvu' => $bank['cbu'],
        ];
        $prompt = "Analizá este archivo como comprobante de transferencia bancaria "
            . "para una revisión preliminar, no como aprobación de pago. "
            . "Extraé solamente datos que sean visibles; usá null si no se leen. "
            . "No inventes ni completes datos faltantes. Marcá anomalías visuales "
            . "como recortes, superposiciones o inconsistencias, sin afirmar fraude.\n\n"
            . "Datos esperados del pedido:\n"
            . json_encode($expected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $payload = [
            'model' => $model,
            'store' => false,
            'reasoning' => ['effort' => 'low'],
            'input' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'input_text', 'text' => $prompt],
                    $document,
                ],
            ]],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'receipt_prevalidation',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ];

        $response = $this->request($payload);
        $extracted = $this->extractStructuredOutput($response);
        $checks = $this->compare($extracted, $order, $bank);

        $requiredMatches = $checks['document'] === true
            && $checks['amount'] === true
            && $checks['recipient'] !== false;
        $confidence = (float) ($extracted['extraction_confidence'] ?? 0);
        $hasReference = trim((string) ($extracted['operation_reference'] ?? '')) !== '';
        $prevalidated = $requiredMatches && $confidence >= 0.65 && $hasReference;

        $highRisk = $checks['document'] === false
            || $checks['amount'] === false
            || $checks['recipient'] === false;
        $status = $prevalidated ? 'prevalidated' : 'review';
        $risk = $prevalidated ? 'low' : ($highRisk ? 'high' : 'medium');
        $summary = $prevalidated
            ? 'Importe y destinatario presentan coincidencia preliminar. Verificar en el banco.'
            : 'La lectura automática necesita revisión manual antes de aprobar.';

        return [
            'status' => $status,
            'risk_level' => $risk,
            'summary' => $summary,
            'model' => $model,
            'result' => [
                'extracted' => $extracted,
                'checks' => $checks,
                'disclaimer' => 'La prevalidación no confirma acreditación bancaria.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'document_looks_like_transfer_receipt' => ['type' => 'boolean'],
                'amount_cents' => ['type' => ['integer', 'null']],
                'currency' => ['type' => ['string', 'null']],
                'transfer_date' => ['type' => ['string', 'null']],
                'recipient_name' => ['type' => ['string', 'null']],
                'recipient_account' => ['type' => ['string', 'null']],
                'operation_reference' => ['type' => ['string', 'null']],
                'visual_anomalies' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'extraction_confidence' => [
                    'type' => 'number',
                    'minimum' => 0,
                    'maximum' => 1,
                ],
                'notes' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => [
                'document_looks_like_transfer_receipt',
                'amount_cents',
                'currency',
                'transfer_date',
                'recipient_name',
                'recipient_account',
                'operation_reference',
                'visual_anomalies',
                'extraction_confidence',
                'notes',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $extracted
     * @param array<string, mixed> $order
     * @param array{holder: string, alias: string, cbu: string} $bank
     * @return array<string, bool|null>
     */
    private function compare(array $extracted, array $order, array $bank): array
    {
        $amount = $extracted['amount_cents'] ?? null;
        $amountMatch = is_int($amount)
            ? $amount === (int) ($order['total_cents'] ?? 0)
            : null;

        $recipientText = $this->normalize(
            (string) ($extracted['recipient_name'] ?? '') . ' '
            . (string) ($extracted['recipient_account'] ?? '')
        );
        $expectedValues = array_values(array_filter([
            $this->normalize($bank['holder']),
            $this->normalize($bank['alias']),
            preg_replace('/\D+/', '', $bank['cbu']),
        ]));
        $recipientMatch = $recipientText === '' || !$expectedValues
            ? null
            : false;
        foreach ($expectedValues as $expected) {
            if ($expected !== '' && str_contains($recipientText, $expected)) {
                $recipientMatch = true;
                break;
            }
        }

        $dateMatch = null;
        $date = trim((string) ($extracted['transfer_date'] ?? ''));
        if ($date !== '') {
            try {
                $transferDate = new DateTimeImmutable($date);
                $created = new DateTimeImmutable((string) ($order['created_at'] ?? 'now'));
                $today = new DateTimeImmutable('today +1 day');
                $dateMatch = $transferDate >= $created->modify('-1 day')
                    && $transferDate <= $today;
            } catch (\Throwable) {
                $dateMatch = false;
            }
        }

        return [
            'document' => (bool) ($extracted['document_looks_like_transfer_receipt'] ?? false),
            'amount' => $amountMatch,
            'currency' => isset($extracted['currency'])
                ? in_array(strtoupper((string) $extracted['currency']), ['ARS', 'ARG', '$'], true)
                : null,
            'recipient' => $recipientMatch,
            'date_plausible' => $dateMatch,
            'operation_reference_present' => trim(
                (string) ($extracted['operation_reference'] ?? '')
            ) !== '',
        ];
    }

    private function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function request(array $payload): array
    {
        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $url = rtrim(
            (string) ($this->config['openai_base_url'] ?? 'https://api.openai.com/v1'),
            '/'
        ) . '/responses';
        $headers = [
            'Authorization: Bearer ' . (string) $this->config['openai_api_key'],
            'Content-Type: application/json',
        ];
        $status = 0;
        $body = false;

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle === false) {
                throw new \RuntimeException('No se pudo iniciar la conexión con OpenAI.');
            }
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 45,
            ]);
            $body = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $curlError = curl_error($handle);
            curl_close($handle);
            if ($body === false) {
                throw new \RuntimeException('OpenAI no respondió: ' . $curlError);
            }
        } else {
            $context = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 45,
                'ignore_errors' => true,
            ]]);
            $body = @file_get_contents($url, false, $context);
            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $match)) {
                    $status = (int) $match[1];
                }
            }
            if ($body === false) {
                throw new \RuntimeException('No se pudo conectar con OpenAI.');
            }
        }

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('OpenAI devolvió una respuesta inválida.');
        }
        if ($status < 200 || $status >= 300) {
            $message = (string) ($decoded['error']['message'] ?? 'Error de OpenAI.');
            throw new \RuntimeException($message);
        }

        return $decoded;
    }

    /** @param array<string, mixed> $response @return array<string, mixed> */
    private function extractStructuredOutput(array $response): array
    {
        foreach (($response['output'] ?? []) as $output) {
            if (!is_array($output) || ($output['type'] ?? '') !== 'message') {
                continue;
            }
            foreach (($output['content'] ?? []) as $content) {
                if (!is_array($content) || ($content['type'] ?? '') !== 'output_text') {
                    continue;
                }
                $parsed = json_decode((string) ($content['text'] ?? ''), true);
                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        }
        throw new \RuntimeException('OpenAI no devolvió los datos estructurados esperados.');
    }
}
