<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;
use Throwable;

/** Envía avisos transaccionales desde una cola mediante SMTP seguro. */
final class MailService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly PDO $pdo,
        private readonly array $config
    ) {
    }

    /** @return array{sent:int,retried:int,failed:int,disabled:bool} */
    public function process(int $limit = 20): array
    {
        if (empty($this->config['mail_enabled'])) {
            return ['sent' => 0, 'retried' => 0, 'failed' => 0, 'disabled' => true];
        }

        $this->assertConfiguration();
        $limit = max(1, min(100, $limit));
        $this->pdo->exec(
            "UPDATE mail_queue SET status = 'pending'
             WHERE status = 'sending' AND available_at <= CURRENT_TIMESTAMP"
        );
        $messages = $this->pdo->query(
            "SELECT * FROM mail_queue
             WHERE status = 'pending' AND available_at <= CURRENT_TIMESTAMP
               AND attempts < 5
             ORDER BY id LIMIT " . $limit
        )->fetchAll();
        $result = ['sent' => 0, 'retried' => 0, 'failed' => 0, 'disabled' => false];

        foreach ($messages as $message) {
            $claim = $this->pdo->prepare(
                "UPDATE mail_queue SET status = 'sending', attempts = attempts + 1,
                 available_at = datetime('now', '+10 minutes')
                 WHERE id = :id AND status = 'pending'"
            );
            $claim->execute(['id' => $message['id']]);
            if ($claim->rowCount() !== 1) continue;

            try {
                $payload = json_decode((string) $message['payload_json'], true, 512, JSON_THROW_ON_ERROR);
                if (
                    !is_array($payload)
                    || !in_array(($payload['audience'] ?? ''), ['internal', 'customer'], true)
                    || (string) ($message['template'] ?? '') !== 'order_created'
                ) {
                    throw new \RuntimeException('La cola contiene un tipo de mensaje no permitido.');
                }
                $this->send(
                    (string) $message['recipient'],
                    (string) $message['subject'],
                    $this->renderOrder($payload)
                );
                $update = $this->pdo->prepare(
                    "UPDATE mail_queue SET status = 'sent', sent_at = CURRENT_TIMESTAMP,
                     last_error = NULL WHERE id = :id"
                );
                $update->execute(['id' => $message['id']]);
                $result['sent']++;
            } catch (Throwable $exception) {
                $attempt = (int) $message['attempts'] + 1;
                $final = $attempt >= 5;
                $update = $this->pdo->prepare(
                    "UPDATE mail_queue SET status = :status, last_error = :error,
                     available_at = CASE WHEN :status = 'pending'
                       THEN datetime('now', '+5 minutes') ELSE available_at END
                     WHERE id = :id"
                );
                $update->execute([
                    'status' => $final ? 'failed' : 'pending',
                    'error' => substr($exception->getMessage(), 0, 500),
                    'id' => $message['id'],
                ]);
                $result[$final ? 'failed' : 'retried']++;
            }
        }

        return $result;
    }

    /** Envía una prueba directa sin activar ni consumir la cola de ventas. */
    public function sendTest(string $recipient): void
    {
        $this->assertConfiguration();
        $this->send(
            $recipient,
            'Prueba de correo · Laboratorio Digital',
            '<!doctype html><html lang="es"><body style="margin:0;background:#f5f3f8;color:#24202a;font:16px Arial,sans-serif"><div style="max-width:620px;margin:auto;padding:28px"><div style="background:#fff;border:1px solid #e3dfea;border-radius:14px;padding:26px"><p style="margin:0 0 12px;color:#72569a;font-size:12px;font-weight:bold;letter-spacing:.08em">LABORATORIO DIGITAL</p><h1 style="margin:0 0 12px;font-size:24px">Amazon SES está conectado</h1><p style="margin:0">Esta es una prueba enviada por la tienda. Si la recibiste, el remitente, Reply-To y la conexión SMTP con Amazon SES están funcionando correctamente.</p></div></div></body></html>'
        );
    }

    private function assertConfiguration(): void
    {
        $transport = (string) ($this->config['mail_transport'] ?? 'ses_smtp');
        if ($transport === 'native') {
            if (trim((string) ($this->config['mail_from'] ?? '')) === '') {
                throw new \RuntimeException('Falta completar el remitente del correo.');
            }
            return;
        }

        if (!in_array($transport, ['smtp', 'ses_smtp'], true)) {
            throw new \RuntimeException('El transporte de correo configurado no es válido.');
        }
        foreach (['mail_from', 'mail_smtp_host', 'mail_smtp_username', 'mail_smtp_password'] as $key) {
            if (trim((string) ($this->config[$key] ?? '')) === '') {
                throw new \RuntimeException('Falta completar la configuración privada de correo.');
            }
        }
    }

    private function send(string $recipient, string $subject, string $html): void
    {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('El destinatario de correo no es válido.');
        }
        $from = (string) $this->config['mail_from'];
        $transport = (string) ($this->config['mail_transport'] ?? 'ses_smtp');
        if ($transport === 'native') {
            $this->sendNative($recipient, $subject, $html);
            return;
        }

        $host = trim((string) $this->config['mail_smtp_host']);
        $port = (int) ($this->config['mail_smtp_port'] ?? 465);
        $encryption = strtolower((string) ($this->config['mail_smtp_encryption'] ?? 'ssl'));
        $username = trim((string) $this->config['mail_smtp_username']);
        $password = (string) $this->config['mail_smtp_password'];
        $target = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $socket = @stream_socket_client($target, $errno, $error, 20, STREAM_CLIENT_CONNECT);
        if (!is_resource($socket)) {
            if ($transport === 'ses_smtp') {
                throw new \RuntimeException('No se pudo conectar de forma segura con Amazon SES: ' . $error);
            }
            // En algunos planes compartidos el servidor bloquea conexiones SMTP
            // hacia sí mismo. El MTA local de Ferozo mantiene el mismo remitente.
            $this->sendNative($recipient, $subject, $html);
            return;
        }
        stream_set_timeout($socket, 20);

        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO laboratoriodigital.com.ar', [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('No se pudo activar TLS en SMTP.');
                }
                $this->command($socket, 'EHLO laboratoriodigital.com.ar', [250]);
            }
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($username), [334]);
            $this->command($socket, base64_encode($password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $cleanSubject = trim(str_replace(["\r", "\n"], '', $subject));
            $encodedSubject = '=?UTF-8?B?' . base64_encode($cleanSubject) . '?=';
            $headers = [
                'To: <' . $recipient . '>',
                'Subject: ' . $encodedSubject,
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . $this->headerText((string) ($this->config['mail_from_name'] ?? 'Laboratorio Digital')) . ' <' . $from . '>',
                'Reply-To: ' . (string) ($this->config['mail_reply_to'] ?? $from),
                'X-Mailer: Laboratorio Digital',
            ];
            $body = str_replace("\r\n", "\n", $html);
            $body = str_replace("\n.", "\n..", $body);
            $message = implode("\r\n", $headers) . "\r\n\r\n"
                . str_replace("\n", "\r\n", $body);
            $this->command($socket, $message . "\r\n.", [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function sendNative(string $recipient, string $subject, string $html): void
    {
        $from = (string) $this->config['mail_from'];
        $cleanSubject = trim(str_replace(["\r", "\n"], '', $subject));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->headerText((string) ($this->config['mail_from_name'] ?? 'Laboratorio Digital')) . ' <' . $from . '>',
            'Reply-To: ' . (string) ($this->config['mail_reply_to'] ?? $from),
            'X-Mailer: Laboratorio Digital',
        ];
        if (!@mail($recipient, $cleanSubject, $html, implode("\r\n", $headers), '-f' . $from)) {
            throw new \RuntimeException('El servidor no pudo entregar el aviso interno.');
        }
    }

    /** @param resource $socket @param list<int> $codes */
    private function command($socket, string $command, array $codes): void
    {
        if (fwrite($socket, $command . "\r\n") === false) {
            throw new \RuntimeException('No se pudo escribir en el SMTP.');
        }
        $this->expect($socket, $codes);
    }

    /** @param resource $socket @param list<int> $codes */
    private function expect($socket, array $codes): void
    {
        $response = '';
        while (($line = fgets($socket, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') break;
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP respondió: ' . trim($response));
        }
    }

    /** @param array<string, mixed> $payload */
    private function renderOrder(array $payload): string
    {
        $rows = '';
        foreach ((array) ($payload['items'] ?? []) as $item) {
            if (!is_array($item)) continue;
            $variant = trim((string) ($item['variant_name'] ?? ''));
            if (preg_match('/^única$/iu', $variant) === 1) $variant = '';
            $rows .= '<tr><td style="padding:10px;border-bottom:1px solid #e8e5ee"><strong>'
                . $this->escape((string) ($item['product_name'] ?? '')) . '</strong>'
                . ($variant !== '' ? '<br><span>' . $this->escape($variant) . '</span>' : '')
                . '</td><td style="padding:10px;text-align:center;border-bottom:1px solid #e8e5ee">'
                . (int) ($item['quantity'] ?? 0) . '</td><td style="padding:10px;text-align:right;border-bottom:1px solid #e8e5ee">'
                . $this->money((int) ($item['line_total_cents'] ?? 0)) . '</td></tr>';
        }

        $customerCopy = ($payload['audience'] ?? '') === 'customer';
        $heading = $customerCopy
            ? '¡Gracias por tu pedido! ' . $this->escape((string) ($payload['public_number'] ?? ''))
            : 'Nueva venta ' . $this->escape((string) ($payload['public_number'] ?? ''));
        $intro = $customerCopy
            ? '<p>Hola ' . $this->escape((string) ($payload['customer_name'] ?? '')) . '. Recibimos correctamente tu compra. Este es el detalle:</p>'
            : '<p><strong>Cliente:</strong> ' . $this->escape((string) ($payload['customer_name'] ?? ''))
                . '<br><strong>WhatsApp:</strong> ' . $this->escape((string) ($payload['customer_phone'] ?? ''))
                . (($payload['customer_email'] ?? '') !== '' ? '<br><strong>Email:</strong> ' . $this->escape((string) $payload['customer_email']) : '') . '</p>';

        return '<!doctype html><html lang="es"><body style="margin:0;background:#f5f3f8;color:#24202a;font:15px Arial,sans-serif">'
            . '<div style="max-width:680px;margin:auto;padding:24px"><div style="background:#fff;border:1px solid #e3dfea;border-radius:14px;padding:24px">'
            . '<p style="color:#72569a;font-size:12px;font-weight:bold;letter-spacing:.08em">LABORATORIO DIGITAL</p>'
            . '<h1 style="margin:0 0 8px">' . $heading . '</h1>' . $intro
            . '<table style="width:100%;border-collapse:collapse"><thead><tr><th style="text-align:left;padding:10px">Producto</th><th style="padding:10px">Cantidad</th><th style="text-align:right;padding:10px">Importe</th></tr></thead><tbody>'
            . $rows . '</tbody></table><p style="font-size:20px;text-align:right"><strong>Total: '
            . $this->money((int) ($payload['total_cents'] ?? 0)) . '</strong></p>'
            . ($customerCopy ? '<p style="margin-top:22px">Nos comunicaremos por WhatsApp para coordinar tu compra. Muchas gracias.</p>' : '')
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

    private function headerText(string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', $value));
    }
}
