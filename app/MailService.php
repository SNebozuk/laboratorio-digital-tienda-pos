<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use Throwable;

final class MailService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config
    ) {
    }

    /** @return array{sent: int, retried: int, failed: int, disabled: bool} */
    public function process(int $limit = 20): array
    {
        if (empty($this->config['mail_enabled'])) {
            return [
                'sent' => 0,
                'retried' => 0,
                'failed' => 0,
                'disabled' => true,
            ];
        }

        $limit = max(1, min(100, $limit));
        $this->pdo->exec(
            "UPDATE mail_queue
             SET status = 'pending'
             WHERE status = 'sending'
               AND available_at <= CURRENT_TIMESTAMP"
        );
        $messages = $this->pdo->query(
            "SELECT *
             FROM mail_queue
             WHERE status = 'pending'
               AND available_at <= CURRENT_TIMESTAMP
               AND attempts < 5
             ORDER BY id
             LIMIT " . $limit
        )->fetchAll();

        $result = [
            'sent' => 0,
            'retried' => 0,
            'failed' => 0,
            'disabled' => false,
        ];

        foreach ($messages as $message) {
            $claim = $this->pdo->prepare(
                "UPDATE mail_queue
                 SET status = 'sending',
                     attempts = attempts + 1,
                     available_at = datetime('now', '+30 minutes')
                 WHERE id = :id
                   AND status = 'pending'"
            );
            $claim->execute(['id' => $message['id']]);
            if ($claim->rowCount() !== 1) {
                continue;
            }

            try {
                $payload = json_decode(
                    (string) $message['payload_json'],
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
                if (!is_array($payload)) {
                    throw new \RuntimeException('El contenido del correo no es válido.');
                }
                $sent = $this->send(
                    (string) $message['recipient'],
                    (string) $message['subject'],
                    $this->render((string) $message['template'], $payload)
                );
                if (!$sent) {
                    throw new \RuntimeException(
                        'El servidor de correo no aceptó el mensaje.'
                    );
                }

                $update = $this->pdo->prepare(
                    "UPDATE mail_queue
                     SET status = 'sent',
                         sent_at = CURRENT_TIMESTAMP,
                         last_error = NULL
                     WHERE id = :id"
                );
                $update->execute(['id' => $message['id']]);
                $result['sent']++;
            } catch (Throwable $exception) {
                $attempt = (int) $message['attempts'] + 1;
                $final = $attempt >= 5;
                $update = $this->pdo->prepare(
                    "UPDATE mail_queue
                     SET status = :status,
                         last_error = :last_error,
                         available_at = CASE
                            WHEN :status = 'pending'
                            THEN datetime('now', '+15 minutes')
                            ELSE available_at
                         END
                     WHERE id = :id"
                );
                $update->execute([
                    'status' => $final ? 'failed' : 'pending',
                    'last_error' => substr($exception->getMessage(), 0, 500),
                    'id' => $message['id'],
                ]);
                $result[$final ? 'failed' : 'retried']++;
            }
        }

        return $result;
    }

    private function send(string $recipient, string $subject, string $html): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('El destinatario del correo no es válido.');
        }

        $from = (string) $this->config['mail_from'];
        $replyTo = (string) $this->config['mail_reply_to'];
        $fromName = $this->cleanHeader((string) $this->config['mail_from_name']);
        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('El remitente de correo no está configurado.');
        }
        if (!filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $replyTo = $from;
        }

        $encodedSubject = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader(
                $this->cleanHeader($subject),
                'UTF-8',
                'B',
                "\r\n"
            )
            : $this->cleanHeader($subject);
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $from . '>',
            'Reply-To: ' . $replyTo,
            'X-Mailer: Laboratorio Digital',
        ]);

        return mail($recipient, $encodedSubject, $html, $headers);
    }

    /** @param array<string, mixed> $payload */
    private function render(string $template, array $payload): string
    {
        $number = $this->escape((string) ($payload['public_number'] ?? ''));
        $name = $this->escape((string) ($payload['customer_name'] ?? ''));
        $title = 'Actualización de tu pedido';
        $content = '';

        switch ($template) {
            case 'order_created':
                $title = 'Recibimos tu pedido';
                $items = is_array($payload['items'] ?? null)
                    ? $payload['items']
                    : [];
                $rows = '';
                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $rows .= '<tr><td>'
                        . $this->escape((string) ($item['product_name'] ?? ''))
                        . '<br><small>'
                        . $this->escape((string) ($item['variant_name'] ?? ''))
                        . ' · '
                        . (int) ($item['quantity'] ?? 0)
                        . ' unidades</small></td><td style="text-align:right">'
                        . $this->money((int) ($item['line_total_cents'] ?? 0))
                        . '</td></tr>';
                }
                $content = '<p>El pedido fue creado y está pendiente de transferencia.</p>'
                    . '<table style="width:100%;border-collapse:collapse">'
                    . $rows
                    . '</table>'
                    . '<p style="font-size:20px"><strong>Total: '
                    . $this->money((int) ($payload['total_cents'] ?? 0))
                    . '</strong></p>'
                    . '<p>Podés informar el pago hasta '
                    . $this->escape(
                        (string) ($payload['payment_deadline_at'] ?? '')
                    )
                    . '.</p>'
                    . $this->actionButton(
                        (string) ($payload['payment_url'] ?? ''),
                        'VER DATOS Y SUBIR COMPROBANTE'
                    );
                break;

            case 'payment_reported':
                $title = 'Comprobante recibido';
                $content = '<p>Recibimos el comprobante y reservamos el stock. '
                    . 'El pago está pendiente de verificación.</p>';
                break;

            case 'payment_approved':
                $title = 'Pago aprobado';
                $content = '<p>El pago fue aprobado y estamos preparando tu pedido.</p>';
                break;

            case 'payment_rejected':
                $title = 'Necesitamos otro comprobante';
                $content = '<p>No pudimos validar el comprobante. Podés volver a '
                    . 'cargarlo hasta '
                    . $this->escape(
                        (string) ($payload['retry_deadline_at'] ?? '')
                    )
                    . '.</p>'
                    . $this->actionButton(
                        (string) ($payload['payment_url'] ?? ''),
                        'VOLVER A SUBIR COMPROBANTE'
                    );
                break;

            case 'order_ready':
                $title = 'Pedido listo para retirar';
                $content = '<p>Tu pedido ya está preparado y podés retirarlo en el local.</p>';
                break;

            case 'order_cancelled':
                $title = 'Pedido cancelado';
                $content = '<p>'
                    . $this->escape(
                        (string) ($payload['detail'] ?? 'El pedido fue cancelado.')
                    )
                    . '</p>';
                break;
        }

        return '<!doctype html><html lang="es"><body style="margin:0;background:#f1f1f1;color:#111;font:16px Arial,sans-serif">'
            . '<div style="max-width:620px;margin:auto;padding:28px 16px">'
            . '<div style="background:#050505;color:#fff;padding:28px;border-radius:14px">'
            . '<p style="margin:0 0 18px;font-size:12px;letter-spacing:.08em">LABORATORIO DIGITAL</p>'
            . '<h1 style="margin:0 0 18px;font-size:28px">'
            . $title
            . '</h1><p>Hola '
            . ($name !== '' ? $name : 'cliente')
            . '.</p><p>Pedido <strong>'
            . $number
            . '</strong></p>'
            . $content
            . '<hr style="border:0;border-top:1px solid #444;margin:24px 0">'
            . '<p style="color:#bbb;font-size:13px">Retiro únicamente en el local. '
            . 'Este mensaje fue enviado por ventas@laboratorio-digital.com.ar.</p>'
            . '</div></div></body></html>';
    }

    private function money(int $cents): string
    {
        return '$ ' . number_format($cents / 100, 0, ',', '.');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function cleanHeader(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }

    private function actionButton(string $url, string $label): string
    {
        $url = trim($url);
        if (
            !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
            return '';
        }

        return '<p style="margin:24px 0">'
            . '<a href="'
            . $this->escape($url)
            . '" style="display:inline-block;padding:13px 18px;border-radius:9px;'
            . 'background:#fff;color:#000;font-weight:bold;text-decoration:none">'
            . $this->escape($label)
            . '</a></p>'
            . '<p style="color:#bbb;font-size:12px">Este enlace es personal. '
            . 'No lo compartas con otras personas.</p>';
    }
}
