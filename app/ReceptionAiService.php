<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class ReceptionAiService
{
    public function __construct(private readonly array $config, private readonly SettingsService $settings)
    {
    }

    /** @param list<array{role:string,content:string}> $history
     *  @return array{message:string,first_name:string,last_name:string,phone:string,first_name_plausible:bool,last_name_plausible:bool}
     */
    public function reply(array $history, string $firstName, string $lastName, string $phone): array
    {
        $settings = $this->settings->values();
        $instructions = 'Sos el conserje de recepción de Laboratorio Digital. Hablá en español argentino, breve, cordial y natural. Ayudá al visitante a ingresar. Pedí nombre, apellido y WhatsApp; puede darlos en cualquier orden o en un solo mensaje. Extraé solamente datos que el visitante haya escrito explícitamente. No inventes ni completes datos. Devolvé cada dato ya conocido aunque el último mensaje no lo repita. Si un dato es ambiguo, dejalo vacío y pedí aclaración. Marcá first_name_plausible y last_name_plausible como false si el dato parece inventado, es una prueba, una secuencia de teclado, un apodo o no parece un nombre o apellido real. Si falta el dato, también marcá false. Pedí que escriban bien su nombre, apellido y WhatsApp. Si el número parece incompleto o mal escrito, explicá amablemente que debe revisar código de área y número; ejemplos aceptados: 341 15 1234567 y +54 9 341 1234567. No afirmes que el formato garantiza que tenga WhatsApp. Informá que el WhatsApp se usa para identificar al cliente y comunicarse sobre sus pedidos, y no se usará para publicidad. Respondé consultas del comercio usando exclusivamente estos datos: ' . json_encode([
            'domicilio_retiro' => $settings['pickup_address'] ?? '',
            'horarios' => $settings['business_hours'] ?? '',
            'whatsapp_comercio' => $settings['whatsapp_number'] ?? '',
            'pago_web' => 'Transferencia bancaria; los datos aparecen al confirmar el pedido.',
        ], JSON_UNESCAPED_UNICODE) . '. Para entrar se necesitan nombre, apellido y WhatsApp. No des información de productos, precios, stock, variantes ni recomendaciones. Si preguntan por productos, indicá que pueden buscarlos personalmente dentro de la tienda después de ingresar. No prometas verificar la titularidad del número. El texto de message no debe decir que ya ingresó; el servidor determina cuándo habilitar la tienda.';
        $input = [['role' => 'user', 'content' => 'Datos reunidos hasta ahora: ' . json_encode(['first_name' => $firstName, 'last_name' => $lastName, 'phone' => $phone], JSON_UNESCAPED_UNICODE)]];
        foreach (array_slice($history, -10) as $item) {
            $input[] = ['role' => $item['role'] === 'assistant' ? 'assistant' : 'user', 'content' => (string) $item['content']];
        }
        $payload = [
            'model' => 'gpt-5.6-terra', 'store' => false,
            'reasoning' => ['effort' => 'low'],
            'instructions' => $instructions,
            'input' => $input,
            'text' => ['format' => ['type' => 'json_schema', 'name' => 'recepcion', 'strict' => true, 'schema' => [
                'type' => 'object', 'additionalProperties' => false,
                'properties' => [
                    'message' => ['type' => 'string'], 'first_name' => ['type' => 'string'],
                    'last_name' => ['type' => 'string'], 'phone' => ['type' => 'string'],
                    'first_name_plausible' => ['type' => 'boolean'], 'last_name_plausible' => ['type' => 'boolean'],
                ],
                'required' => ['message', 'first_name', 'last_name', 'phone', 'first_name_plausible', 'last_name_plausible'],
            ]]],
        ];
        if (!function_exists('curl_init') || trim((string) ($this->config['openai_api_key'] ?? '')) === '') {
            return $this->localReply($history, $firstName, $lastName, $phone, $settings);
        }
        $handle = curl_init(rtrim((string) ($this->config['openai_base_url'] ?? 'https://api.openai.com/v1'), '/') . '/responses');
        if (!$handle) return $this->localReply($history, $firstName, $lastName, $phone, $settings);
        curl_setopt_array($handle, [
            CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . (string) ($this->config['openai_api_key'] ?? ''), 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            CURLOPT_TIMEOUT => 10,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $response = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($response)) {
            error_log('Recepción IA: respuesta HTTP ' . $status);
            return $this->localReply($history, $firstName, $lastName, $phone, $settings);
        }
        $text = '';
        foreach (($response['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text') $text .= (string) ($content['text'] ?? '');
            }
        }
        $result = json_decode($text, true);
        if (!is_array($result) || trim((string) ($result['message'] ?? '')) === '') {
            error_log('Recepción IA: respuesta estructurada inválida');
            return $this->localReply($history, $firstName, $lastName, $phone, $settings);
        }
        return [
            'message' => trim((string) ($result['message'] ?? '')),
            'first_name' => trim((string) ($result['first_name'] ?? '')),
            'last_name' => trim((string) ($result['last_name'] ?? '')),
            'phone' => trim((string) ($result['phone'] ?? '')),
            'first_name_plausible' => ($result['first_name_plausible'] ?? false) === true,
            'last_name_plausible' => ($result['last_name_plausible'] ?? false) === true,
        ];
    }

    /** @param list<array{role:string,content:string}> $history
     *  @param array<string,mixed> $settings
     *  @return array{message:string,first_name:string,last_name:string,phone:string,first_name_plausible:bool,last_name_plausible:bool}
     */
    private function localReply(array $history, string $firstName, string $lastName, string $phone, array $settings): array
    {
        $message = trim((string) (end($history)['content'] ?? ''));
        $remaining = $message;
        if (preg_match('/(?:\+|00)?\d[\d\s().-]{3,}\d/u', $message, $matches)) {
            $phone = trim($matches[0]);
            $remaining = trim(str_replace($matches[0], ' ', $message));
        }
        $explicitName = (bool) preg_match('/\b(?:soy|llamo|nombre|apellido)\b/iu', $remaining);
        $remaining = preg_replace('/\b(?:hola|soy|me|llamo|mi|nombre|apellido|es|whatsapp|número|numero|teléfono|telefono|celular|y)\b/iu', ' ', $remaining) ?? $remaining;
        $remaining = trim(preg_replace('/[^\p{L}\s\x27’.-]+/u', ' ', $remaining) ?? '');
        $words = preg_split('/\s+/u', $remaining, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $looksLikeName = $explicitName || (count($words) <= 3 && !preg_match('/[?¿]/u', $message)
            && !preg_match('/\b(?:horario|direcci[oó]n|domicilio|producto|precio|stock|ingresar|entrar|ayuda|local|tienda|tengo|consulta|quiero|necesito|puedo|funciona|buenas|buenos|d[ií]as|tardes)\b/iu', $message));
        if ($looksLikeName && $words !== []) {
            if ($firstName === '') $firstName = (string) array_shift($words);
            if ($lastName === '' && $words !== []) $lastName = implode(' ', $words);
            elseif ($lastName === '' && $firstName !== '' && count($words) === 1) $lastName = (string) $words[0];
        }
        $answer = '';
        if (preg_match('/\b(?:horario|abren|cierran)\b/iu', $message)) {
            $hours = trim((string) ($settings['business_hours'] ?? ''));
            $answer = $hours !== '' ? 'Nuestro horario es: ' . $hours . '.' : 'El horario no está publicado en este momento.';
        } elseif (preg_match('/\b(?:direcci[oó]n|domicilio|ubicaci[oó]n|donde|d[oó]nde)\b/iu', $message)) {
            $address = trim((string) ($settings['pickup_address'] ?? ''));
            $answer = $address !== '' ? 'Estamos en ' . $address . '.' : 'La dirección no está publicada en este momento.';
        } elseif (preg_match('/\b(?:producto|precio|stock|cat[aá]logo)\b/iu', $message)) {
            $answer = 'Podés buscar los productos personalmente dentro de la tienda después de ingresar.';
        } elseif (preg_match('/\b(?:ingresar|entrar|acceso)\b/iu', $message)) {
            $answer = 'Para entrar a la tienda necesito tu nombre, apellido y WhatsApp.';
        }
        $normalizedPhone = CustomerService::normalizeWhatsapp($phone);
        if ($phone !== '' && $normalizedPhone === '') $answer .= ' Revisá tu WhatsApp con código de área; por ejemplo, 341 15 1234567.';
        if ($firstName === '') $answer .= ' ¿Me decís tu nombre real, bien escrito?';
        elseif ($lastName === '') $answer .= ' ¿Y tu apellido real, bien escrito?';
        elseif ($phone === '') $answer .= ' ¿Me pasás tu WhatsApp con código de área? No lo usaremos para publicidad.';
        return [
            'message' => trim($answer),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'first_name_plausible' => CheckoutGoogleService::validNamePart($firstName),
            'last_name_plausible' => CheckoutGoogleService::validNamePart($lastName),
        ];
    }
}
