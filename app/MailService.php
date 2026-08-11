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
        if (empty($this->runtimeConfig()['mail_enabled'])) {
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

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        $config = $this->runtimeConfig();
        $counts = ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0];
        foreach ($this->pdo->query('SELECT status, COUNT(*) AS total FROM mail_queue GROUP BY status')->fetchAll() as $row) {
            $status = (string) $row['status'];
            if (array_key_exists($status, $counts)) $counts[$status] = (int) $row['total'];
        }
        $latestError = $this->pdo->query("SELECT last_error FROM mail_queue WHERE last_error IS NOT NULL AND last_error <> '' ORDER BY id DESC LIMIT 1")->fetchColumn();

        return [
            'enabled' => !empty($config['mail_enabled']),
            'smtp_ready' => trim((string) ($config['mail_smtp_host'] ?? '')) !== ''
                && trim((string) ($config['mail_smtp_username'] ?? '')) !== ''
                && trim((string) ($config['mail_smtp_password'] ?? '')) !== '',
            'host' => (string) ($config['mail_smtp_host'] ?? ''),
            'port' => (int) ($config['mail_smtp_port'] ?? 0),
            'encryption' => (string) ($config['mail_smtp_encryption'] ?? ''),
            'counts' => $counts,
            'latest_error' => $latestError === false ? null : (string) $latestError,
        ];
    }

    private function send(string $recipient, string $subject, string $html): bool
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('El destinatario del correo no es válido.');
        }

        $config = $this->runtimeConfig();
        $from = (string) $config['mail_from'];
        $replyTo = (string) $config['mail_reply_to'];
        $fromName = $this->cleanHeader((string) $config['mail_from_name']);
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

        $transport = strtolower((string) ($config['mail_transport'] ?? 'smtp'));
        if ($transport === 'mail') {
            return mail($recipient, $encodedSubject, $html, $headers);
        }
        if ($transport !== 'smtp') {
            throw new \RuntimeException('El transporte de correo no es válido.');
        }

        return $this->sendSmtp($recipient, $encodedSubject, $html, $from, $headers, $config);
    }

    /** @param array<string, mixed> $config */
    private function sendSmtp(string $recipient, string $subject, string $html, string $from, string $headers, array $config): bool
    {
        $host = trim((string) ($config['mail_smtp_host'] ?? ''));
        $port = (int) ($config['mail_smtp_port'] ?? 587);
        $encryption = strtolower((string) ($config['mail_smtp_encryption'] ?? 'tls'));
        $username = trim((string) ($config['mail_smtp_username'] ?? ''));
        $password = (string) ($config['mail_smtp_password'] ?? '');
        if ($host === '' || $port < 1 || $port > 65535 || $username === '' || $password === '') {
            throw new \RuntimeException('Falta configurar el SMTP autenticado.');
        }
        if (!in_array($encryption, ['tls', 'ssl', 'none'], true)) {
            throw new \RuntimeException('El cifrado SMTP no es válido.');
        }

        $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $error, 20, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            throw new \RuntimeException('No se pudo conectar al SMTP: ' . $error);
        }
        stream_set_timeout($socket, 20);
        try {
            $this->smtpExpect($socket, [220]);
            $this->smtpCommand($socket, 'EHLO laboratorio-digital', [250]);
            if ($encryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo activar TLS en SMTP.');
                }
                $this->smtpCommand($socket, 'EHLO laboratorio-digital', [250]);
            }
            $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
            $this->smtpCommand($socket, base64_encode($username), [334]);
            $this->smtpCommand($socket, base64_encode($password), [235]);
            $this->smtpCommand($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);
            $body = str_replace("\r\n", "\n", $html);
            $body = str_replace("\n.", "\n..", $body);
            $message = 'To: <' . $recipient . ">\r\n"
                . 'Subject: ' . $subject . "\r\n"
                . $headers . "\r\n\r\n"
                . str_replace("\n", "\r\n", $body);
            $this->smtpCommand($socket, $message . "\r\n.", [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }

        return true;
    }

    /** @param resource $socket @param list<int> $codes */
    private function smtpCommand($socket, string $command, array $codes): void
    {
        if (fwrite($socket, $command . "\r\n") === false) {
            throw new \RuntimeException('No se pudo escribir en el SMTP.');
        }
        $this->smtpExpect($socket, $codes);
    }

    /** @param resource $socket @param list<int> $codes */
    private function smtpExpect($socket, array $codes): void
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP respondió: ' . trim($response));
        }
    }

    /** @param array<string, mixed> $payload */
    private function render(string $template, array $payload): string
    {
        $number = $this->escape((string) ($payload['public_number'] ?? ''));
        $name = $this->escape((string) ($payload['customer_name'] ?? ''));
        $internal = ($payload['audience'] ?? '') === 'internal';
        $title = 'Actualización de tu pedido';
        $content = '';

        switch ($template) {
            case 'order_created':
                $cashOrder = (string) ($payload['payment_method'] ?? '') === 'cash';
                $title = $internal ? 'Nuevo pedido web' : 'Recibimos tu pedido';
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
                        . ' unidades × '
                        . $this->money((int) ($item['unit_price_cents'] ?? 0))
                        . '</small></td><td style="text-align:right">'
                        . $this->money((int) ($item['line_total_cents'] ?? 0))
                        . '</td></tr>';
                }
                $content = ($cashOrder
                    ? ($internal
                        ? '<p>Ingresó un pedido para pagar en efectivo. El stock está reservado durante 2 horas.</p>'
                        : '<p>Elegiste pagar en efectivo al retirar. El stock está reservado solamente durante 2 horas.</p>')
                    : ($internal
                        ? '<p>Ingresó un nuevo pedido pendiente de transferencia.</p>'
                        : '<p>El pedido fue creado y está pendiente de transferencia.</p>'))
                    . '<table style="width:100%;border-collapse:collapse">'
                    . $rows
                    . '</table>'
                    . '<p style="font-size:20px"><strong>Total: '
                    . $this->money((int) ($payload['total_cents'] ?? 0))
                    . '</strong></p>'
                    . '<p>' . ($cashOrder
                        ? 'Tenés que retirar y pagar antes del '
                        : 'Cuando realices la transferencia, avisanos por WhatsApp. ')
                    . ($cashOrder ? $this->escape(
                        (string) ($payload['payment_deadline_at'] ?? '')
                    ) : '')
                    . '.</p>'
                    . ($cashOrder
                        ? '<p><strong>Al vencer el plazo, el pedido se cancela y los productos vuelven al stock.</strong></p>'
                        : $this->actionButton(
                        $internal
                            ? rtrim((string) ($this->config['base_url'] ?? ''), '/')
                                . '/admin/'
                            : rtrim((string) ($this->config['base_url'] ?? ''), '/') . '/',
                        $internal
                            ? 'ABRIR ADMINISTRACIÓN'
                            : 'VER LA TIENDA'
                    ));
                if ($internal) {
                    $content .= '<p>WhatsApp del cliente: <strong>'
                        . $this->escape(
                            (string) ($payload['customer_phone'] ?? '')
                        )
                        . '</strong><br>Email del cliente: <strong>'
                        . $this->escape((string) ($payload['customer_email'] ?? 'Sin email'))
                        . '</strong></p>';
                }
                break;

            case 'payment_reported':
                $title = 'Pago informado';
                $content = '<p>Recibimos tu aviso de pago y verificaremos la acreditación.</p>';
                break;

            case 'payment_approved':
                $title = 'Pago aprobado';
                $content = '<p>El pago fue aprobado y estamos preparando tu pedido.</p>';
                break;

            case 'payment_rejected':
                $title = 'Necesitamos revisar el pago';
                $content = '<p>Necesitamos que te comuniques por WhatsApp para revisar los datos del pago.</p>';
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

        if (!$internal) {
            $customMessage = $this->customMessage($template, $payload);
            if ($customMessage !== '') {
                $content = '<p>' . nl2br($this->escape($customMessage)) . '</p>' . $content;
            }
        }

        return '<!doctype html><html lang="es"><body style="margin:0;background:#f1f1f1;color:#111;font:16px Arial,sans-serif">'
            . '<div style="max-width:620px;margin:auto;padding:28px 16px">'
            . '<div style="background:#050505;color:#fff;padding:28px;border-radius:14px">'
            . '<p style="margin:0 0 18px;font-size:12px;letter-spacing:.08em">LABORATORIO DIGITAL</p>'
            . '<h1 style="margin:0 0 18px;font-size:28px">'
            . $title
            . '</h1><p>Hola '
            . ($internal ? 'equipo' : ($name !== '' ? $name : 'cliente'))
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

    /** @return array<string, mixed> */
    private function runtimeConfig(): array
    {
        $keys = [
            'mail_enabled', 'mail_from', 'mail_from_name', 'mail_reply_to',
            'mail_smtp_host', 'mail_smtp_port', 'mail_smtp_encryption',
            'mail_smtp_username',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $query = $this->pdo->prepare(
            'SELECT key, value FROM settings WHERE key IN (' . $placeholders . ')'
        );
        $query->execute($keys);
        $stored = [];
        foreach ($query->fetchAll() as $row) {
            $stored[(string) $row['key']] = (string) $row['value'];
        }
        $config = $this->config;
        foreach ($keys as $key) {
            if (!empty($config['mail_smtp_force_config'])) {
                continue;
            }
            // Una habilitación explícita en la configuración privada del servidor
            // tiene prioridad sobre el valor inicial de la base de datos.
            if ($key === 'mail_enabled' && !empty($config[$key])) {
                continue;
            }
            if (array_key_exists($key, $stored) && $stored[$key] !== '') {
                $config[$key] = $stored[$key];
            }
        }
        $config['mail_enabled'] = filter_var(
            $config['mail_enabled'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        return $config;
    }

    /** @param array<string, mixed> $payload */
    private function customMessage(string $template, array $payload): string
    {
        $query = $this->pdo->prepare(
            'SELECT value FROM settings WHERE key = :key LIMIT 1'
        );
        $query->execute(['key' => 'mail_message_' . $template]);
        $message = trim((string) $query->fetchColumn());
        if ($message === '') {
            return '';
        }
        $replacements = [
            '{{cliente}}' => (string) ($payload['customer_name'] ?? ''),
            '{{pedido}}' => (string) ($payload['public_number'] ?? ''),
            '{{total}}' => $this->money((int) ($payload['total_cents'] ?? 0)),
            '{{plazo}}' => (string) ($payload['payment_deadline_at']
                ?? $payload['retry_deadline_at'] ?? ''),
        ];

        return strtr($message, $replacements);
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
