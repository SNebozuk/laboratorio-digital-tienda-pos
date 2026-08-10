<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use DateTimeImmutable;
use PDO;
use Throwable;

final class PaymentProofService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly PDO $pdo,
        private readonly StockService $stock,
        private readonly array $config,
        private readonly ReceiptAiService $receiptAi
    ) {
    }

    /**
     * Consulta pública limitada por un token aleatorio. No expone datos del
     * cliente, archivos previos ni información administrativa.
     *
     * @return array<string, mixed>
     */
    public function publicStatus(int $orderId, string $uploadToken): array
    {
        $order = $this->publicOrder($orderId, $uploadToken);
        $status = (string) $order['status'];
        $deadline = $status === 'rejected'
            ? $order['rejection_deadline_at']
            : $order['payment_deadline_at'];
        $canUpload = in_array($status, ['pending_payment', 'rejected'], true)
            && $deadline !== null
            && new DateTimeImmutable((string) $deadline) >= new DateTimeImmutable();

        $itemsQuery = $this->pdo->prepare(
            'SELECT product_name, variant_name, quantity,
                    unit_price_cents, line_total_cents
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id'
        );
        $itemsQuery->execute(['order_id' => $orderId]);

        return [
            'id' => (int) $order['id'],
            'public_number' => (string) $order['public_number'],
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'total_cents' => (int) $order['total_cents'],
            'deadline_at' => $deadline,
            'can_upload' => $canUpload,
            'stock_reserved' => $order['stock_reserved_at'] !== null,
            'items' => $itemsQuery->fetchAll(),
            'bank' => [
                'holder' => $this->stringSetting(
                    'bank_holder',
                    'Laboratorio Digital'
                ),
                'alias' => $this->stringSetting('bank_alias', ''),
                'cbu' => $this->stringSetting('bank_cbu', ''),
            ],
            'pickup_address' => $this->stringSetting('pickup_address', ''),
            'business_hours' => $this->stringSetting(
                'business_hours',
                'Lunes a viernes de 9 a 17 h'
            ),
            'whatsapp_number' => $this->stringSetting(
                'whatsapp_number',
                '5493415699338'
            ),
            'proof_max_bytes' => max(
                1024,
                $this->integerSetting('proof_max_bytes', 8 * 1024 * 1024)
            ),
        ];
    }

    /**
     * @param array<string, mixed> $upload Elemento de $_FILES.
     * @return array<string, mixed>
     */
    public function receive(
        int $orderId,
        string $uploadToken,
        array $upload
    ): array {
        $order = $this->publicOrder($orderId, $uploadToken);
        $file = $this->validateUpload($upload);
        $stored = $this->storeFile($file);
        $proofId = 0;

        $aiResult = [
            'status' => 'failed',
            'risk_level' => null,
            'summary' => 'No se pudo ejecutar la prevalidación automática.',
            'model' => null,
            'result' => null,
        ];
        try {
            $aiResult = $this->receiptAi->prevalidate(
                $stored['absolute_path'],
                $stored['mime_type'],
                $order,
                [
                    'holder' => $this->stringSetting(
                        'bank_holder',
                        'Laboratorio Digital'
                    ),
                    'alias' => $this->stringSetting('bank_alias', ''),
                    'cbu' => $this->stringSetting('bank_cbu', ''),
                ]
            );
        } catch (Throwable $exception) {
            error_log('Prevalidación de comprobante: ' . $exception->getMessage());
        }
        if ($this->isClearlyUnrelatedUpload($aiResult)) {
            @unlink($stored['absolute_path']);
            throw new ValidationException(
                'El archivo no parece ser un comprobante de una transferencia realizada. '
                . 'Subí el comprobante emitido por tu banco o billetera.'
            );
        }

        try {
            $this->stock->reserveForReportedPayment(
                $orderId,
                null,
                function (PDO $pdo, array $lockedOrder) use (
                    $uploadToken,
                    $stored,
                    &$proofId
                ): void {
                    $expectedHash = (string) ($lockedOrder['upload_token_hash'] ?? '');
                    $providedHash = hash('sha256', $uploadToken);
                    if (
                        $expectedHash === ''
                        || !hash_equals($expectedHash, $providedHash)
                    ) {
                        throw new AuthorizationException(
                            'El enlace para subir el comprobante no es válido.'
                        );
                    }

                    $insert = $pdo->prepare(
                        'INSERT INTO payment_proofs(
                            order_id, storage_key, original_name,
                            mime_type, size_bytes, sha256
                         ) VALUES(
                            :order_id, :storage_key, :original_name,
                            :mime_type, :size_bytes, :sha256
                         )'
                    );
                    $insert->execute([
                        'order_id' => $lockedOrder['id'],
                        'storage_key' => $stored['storage_key'],
                        'original_name' => $stored['original_name'],
                        'mime_type' => $stored['mime_type'],
                        'size_bytes' => $stored['size_bytes'],
                        'sha256' => $stored['sha256'],
                    ]);
                    $proofId = (int) $pdo->lastInsertId();

                    if (!empty($lockedOrder['customer_email'])) {
                        $mail = $pdo->prepare(
                            'INSERT INTO mail_queue(
                                order_id, recipient, subject, template, payload_json
                             ) VALUES(
                                :order_id, :recipient, :subject, :template, :payload_json
                             )'
                        );
                        $mail->execute([
                            'order_id' => $lockedOrder['id'],
                            'recipient' => $lockedOrder['customer_email'],
                            'subject' => 'Comprobante recibido · '
                                . $lockedOrder['public_number'],
                            'template' => 'payment_reported',
                            'payload_json' => json_encode([
                                'public_number' => $lockedOrder['public_number'],
                                'customer_name' => $lockedOrder['customer_name'],
                            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        ]);
                    }
                }
            );
        } catch (Throwable $exception) {
            @unlink($stored['absolute_path']);
            throw $exception;
        }

        $this->saveAiResult($proofId, $aiResult);

        return [
            'order_id' => $orderId,
            'public_number' => $order['public_number'],
            'status' => 'payment_reported',
            'proof_name' => $stored['original_name'],
            'stock_reserved' => true,
            'prevalidation_status' => $aiResult['status'],
        ];
    }

    /**
     * Devuelve solo la información necesaria para transmitir el archivo después
     * de que el controlador haya validado un usuario administrador.
     *
     * @return array{path: string, mime_type: string, original_name: string}
     */
    public function protectedFile(int $proofId): array
    {
        $query = $this->pdo->prepare(
            'SELECT storage_key, mime_type, original_name
             FROM payment_proofs
             WHERE id = :id'
        );
        $query->execute(['id' => $proofId]);
        $proof = $query->fetch();
        if (!$proof) {
            throw new ValidationException('El comprobante no existe.');
        }

        $path = $this->storageRoot() . '/' . ltrim($proof['storage_key'], '/\\');
        if (!is_file($path)) {
            throw new \RuntimeException('El archivo del comprobante no está disponible.');
        }

        return [
            'path' => $path,
            'mime_type' => (string) $proof['mime_type'],
            'original_name' => (string) $proof['original_name'],
        ];
    }

    /** @return array<string, mixed> */
    public function analysis(int $proofId): array
    {
        $query = $this->pdo->prepare(
            'SELECT id, order_id, ai_status, ai_risk_level, ai_summary,
                    ai_result_json, ai_model, ai_checked_at
             FROM payment_proofs
             WHERE id = :id'
        );
        $query->execute(['id' => $proofId]);
        $proof = $query->fetch();
        if (!$proof) {
            throw new ValidationException('El comprobante no existe.');
        }
        $decoded = null;
        if (!empty($proof['ai_result_json'])) {
            $candidate = json_decode((string) $proof['ai_result_json'], true);
            $decoded = is_array($candidate) ? $candidate : null;
        }

        return [
            'proof_id' => (int) $proof['id'],
            'order_id' => (int) $proof['order_id'],
            'status' => (string) $proof['ai_status'],
            'risk_level' => $proof['ai_risk_level'],
            'summary' => (string) ($proof['ai_summary'] ?? ''),
            'model' => $proof['ai_model'],
            'checked_at' => $proof['ai_checked_at'],
            'result' => $decoded,
        ];
    }

    /** @return array<string, mixed> */
    private function publicOrder(int $orderId, string $uploadToken): array
    {
        $query = $this->pdo->prepare(
            'SELECT id, public_number, upload_token_hash, status,
                    total_cents, payment_deadline_at, rejection_deadline_at,
                    stock_reserved_at, created_at
             FROM orders
             WHERE id = :id'
        );
        $query->execute(['id' => $orderId]);
        $order = $query->fetch();

        if (!$order) {
            throw new AuthorizationException(
                'El enlace para subir el comprobante no es válido.'
            );
        }
        if ($uploadToken === '' || !hash_equals(
            (string) $order['upload_token_hash'],
            hash('sha256', $uploadToken)
        )) {
            throw new AuthorizationException(
                'El enlace para subir el comprobante no es válido.'
            );
        }

        return $order;
    }

    /**
     * @param array<string, mixed> $upload
     * @return array{tmp_name: string, original_name: string, mime_type: string, size_bytes: int, extension: string}
     */
    private function validateUpload(array $upload): array
    {
        if ((int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new ValidationException('No se pudo recibir el comprobante.');
        }

        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        $maxBytes = max(1024, $this->integerSetting('proof_max_bytes', 8 * 1024 * 1024));

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new ValidationException('La carga del archivo no es válida.');
        }
        if ($size < 1 || $size > $maxBytes) {
            throw new ValidationException(
                'El comprobante supera el límite permitido de '
                . round($maxBytes / 1024 / 1024)
                . ' MB.'
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = (string) $finfo->file($tmpName);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];
        if (!isset($extensions[$mimeType])) {
            throw new ValidationException('Solo se aceptan archivos JPG, PNG o PDF.');
        }

        $originalName = basename((string) ($upload['name'] ?? 'comprobante'));

        return [
            'tmp_name' => $tmpName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'size_bytes' => $size,
            'extension' => $extensions[$mimeType],
        ];
    }

    /**
     * @param array{tmp_name: string, original_name: string, mime_type: string, size_bytes: int, extension: string} $file
     * @return array{storage_key: string, absolute_path: string, original_name: string, mime_type: string, size_bytes: int, sha256: string}
     */
    private function storeFile(array $file): array
    {
        $relativeDirectory = 'proofs/' . date('Y/m');
        $directory = $this->storageRoot() . '/' . $relativeDirectory;
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('No se pudo crear el almacenamiento de comprobantes.');
        }

        $filename = bin2hex(random_bytes(24)) . '.' . $file['extension'];
        $absolutePath = $directory . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            throw new \RuntimeException('No se pudo guardar el comprobante.');
        }
        @chmod($absolutePath, 0660);

        return [
            'storage_key' => $relativeDirectory . '/' . $filename,
            'absolute_path' => $absolutePath,
            'original_name' => $file['original_name'],
            'mime_type' => $file['mime_type'],
            'size_bytes' => $file['size_bytes'],
            'sha256' => hash_file('sha256', $absolutePath),
        ];
    }

    private function storageRoot(): string
    {
        return rtrim((string) $this->config['storage_path'], '/\\');
    }

    /** @param array<string, mixed> $result */
    private function isClearlyUnrelatedUpload(array $result): bool
    {
        if (($result['status'] ?? '') !== 'review' || ($result['risk_level'] ?? '') !== 'high') {
            return false;
        }
        $extracted = $result['result']['extracted'] ?? null;
        if (!is_array($extracted) || (float) ($extracted['extraction_confidence'] ?? 0) < 0.85) {
            return false;
        }

        return ($extracted['document_looks_like_transfer_receipt'] ?? true) === false
            || in_array(
                (string) ($extracted['document_type'] ?? ''),
                ['payment_request', 'account_details', 'invoice', 'unrelated'],
                true
            )
            || in_array(
                (string) ($extracted['transfer_status'] ?? ''),
                ['pending', 'failed'],
                true
            );
    }

    private function integerSetting(string $key, int $default): int
    {
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $query->execute(['key' => $key]);
        $value = $query->fetchColumn();

        return $value === false ? $default : (int) $value;
    }

    private function stringSetting(string $key, string $default): string
    {
        $query = $this->pdo->prepare('SELECT value FROM settings WHERE key = :key');
        $query->execute(['key' => $key]);
        $value = $query->fetchColumn();

        return $value === false ? $default : (string) $value;
    }

    /** @param array<string, mixed> $result */
    private function saveAiResult(int $proofId, array $result): void
    {
        if ($proofId < 1) {
            return;
        }
        $update = $this->pdo->prepare(
            'UPDATE payment_proofs
             SET ai_status = :status,
                 ai_risk_level = :risk_level,
                 ai_summary = :summary,
                 ai_result_json = :result_json,
                 ai_model = :model,
                 ai_checked_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );
        $update->execute([
            'status' => (string) ($result['status'] ?? 'failed'),
            'risk_level' => $result['risk_level'] ?? null,
            'summary' => (string) ($result['summary'] ?? ''),
            'result_json' => isset($result['result'])
                ? json_encode(
                    $result['result'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
                : null,
            'model' => $result['model'] ?? null,
            'id' => $proofId,
        ]);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending_payment' => 'Pendiente de pago',
            'payment_reported' => 'Pago informado',
            'rejected' => 'Comprobante rechazado',
            'paid_prepare' => 'Pagado / preparar',
            'ready_pickup' => 'Listo para retirar',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => 'En proceso',
        };
    }
}
