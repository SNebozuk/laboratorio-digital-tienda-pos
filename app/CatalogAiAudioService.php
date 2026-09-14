<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class CatalogAiAudioService
{
    public function __construct(private readonly array $config)
    {
    }

    /** @param array<string,mixed> $file */
    public function transcribe(array $file): string
    {
        if (empty($this->config['openai_api_key'])) throw new \RuntimeException('La transcripción no está configurada.');
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) throw new ValidationException('No recibí el audio.');
        if ((int) ($file['size'] ?? 0) > 10 * 1024 * 1024) throw new ValidationException('El audio supera el límite de 10 MB.');
        $mime = (string) ($file['type'] ?? 'audio/webm');
        $audio = curl_file_create((string) $file['tmp_name'], $mime, 'consulta.webm');
        $handle = curl_init(rtrim((string) ($this->config['openai_base_url'] ?? 'https://api.openai.com/v1'), '/') . '/audio/transcriptions');
        if (!$handle) throw new \RuntimeException('No se pudo iniciar la transcripción.');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . (string) $this->config['openai_api_key']],
            CURLOPT_POSTFIELDS => ['file' => $audio, 'model' => 'gpt-4o-mini-transcribe', 'language' => 'es'],
            CURLOPT_TIMEOUT => 45,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $data = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) throw new \RuntimeException((string) ($data['error']['message'] ?? 'No pude transcribir el audio.'));
        $text = trim((string) ($data['text'] ?? ''));
        if ($text === '') throw new ValidationException('No pude reconocer un mensaje en el audio.');
        return $text;
    }

    public function speech(string $text): string
    {
        $handle = curl_init(rtrim((string) ($this->config['openai_base_url'] ?? 'https://api.openai.com/v1'), '/') . '/audio/speech');
        if (!$handle) throw new \RuntimeException('No se pudo iniciar la voz.');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . (string) $this->config['openai_api_key'], 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['model' => 'gpt-4o-mini-tts', 'voice' => 'marin', 'input' => function_exists('mb_substr') ? mb_substr($text, 0, 4096) : substr($text, 0, 4096), 'instructions' => 'Voz femenina joven, cálida, natural y simpática. Español rioplatense, ritmo conversacional.'], JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 45,
        ]);
        $audio = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if ($status < 200 || $status >= 300 || $audio === false) throw new \RuntimeException('No pude generar la voz.');
        return $audio;
    }
}
