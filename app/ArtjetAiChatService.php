<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class ArtjetAiChatService
{
    public function __construct(private readonly array $config, private readonly ProductService $products, private readonly ArtjetService $artjet, private readonly SettingsService $settings) {}

    /** @param list<array{role:string,content:string}> $history */
    public function reply(array $history): string
    {
        if (trim((string) ($this->config['openai_api_key'] ?? '')) === '') throw new \RuntimeException('La atención online no está disponible en este momento.');
        $artjetDetails = [];
        foreach ($this->artjet->adminList() as $product) $artjetDetails[(int) $product['product_id']] = $product;
        $catalog = [];
        foreach ($this->products->publicCatalog() as $product) {
            if (preg_match('/\bART-?JET\b/i', (string) ($product['name'] ?? '')) !== 1) continue;
            $details = $artjetDetails[(int) $product['id']] ?? [];
            $catalog[] = [
                'id' => (int) $product['id'],
                'producto' => (string) $product['name'],
                'titulo_artjet' => (string) ($details['store_title'] ?? ''),
                'categoria' => (string) ($details['artjet_category'] ?? ''),
                'subcategoria' => (string) ($details['artjet_subcategory'] ?? ''),
                'descripcion_producto' => (string) ($product['description'] ?? ''),
                'descripcion_artjet' => (string) ($details['store_description'] ?? ''),
                'informacion_tecnica' => (string) ($details['technical_info'] ?? ''),
                'variantes' => array_map(static fn (array $variant): array => [
                    'nombre' => (string) ($variant['name'] ?? ''),
                    'precio' => isset($variant['price_cents']) ? (int) $variant['price_cents'] / 100 : null,
                    'stock' => $variant['available_stock'] === null ? null : (int) $variant['available_stock'],
                ], $product['variants'] ?? []),
            ];
        }
        $settings = $this->settings->values();
        $facts = ['horarios' => (string) ($settings['business_hours'] ?? ''), 'ubicacion' => (string) ($settings['pickup_address'] ?? ''), 'forma_de_pago_web' => 'Transferencia bancaria después de confirmar el pedido en la tienda Laboratorio Digital.', 'canales_de_compra' => ['La página Artjet funciona únicamente como catálogo y no permite comprar.', 'Las compras se realizan desde la tienda Laboratorio Digital o personalmente en el local.', 'Los enlaces de producto del catálogo llevan a la tienda Laboratorio Digital.']];
        $input = [];
        foreach (array_slice($history, -12) as $message) {
            $role = ($message['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($message['content'] ?? ''));
            if ($content !== '') $input[] = ['role' => $role, 'content' => $content];
        }
        $searchCriteria = 'Identificá primero el producto, formato, gramaje, acabado, tipo de impresora, tinta, superficie y uso que menciona el cliente. Relacioná sinónimos y necesidades con títulos, categorías, descripciones e información técnica del catálogo. Conservá los datos ya dichos durante la conversación y pedí una aclaración breve solamente si es imprescindible. Compará alternativas únicamente con datos presentes en el catálogo. Nunca completes una compatibilidad o característica por conocimiento externo.';
        $responseCriteria = 'Respondé como especialista en los productos Art-Jet efectivamente vendidos por Laboratorio Digital. Explicá con claridad por qué un producto sirve o no sirve para el uso consultado, apoyándote en su descripción e información técnica. Cuando haya varias opciones, señalá las diferencias relevantes sin abrumar. Mencioná precio o disponibilidad solamente si figuran en los datos actuales. Si el catálogo no permite afirmar algo, decilo con naturalidad en vez de suponerlo.';
        $response = $this->request(['model' => 'gpt-5.6-terra', 'store' => false, 'reasoning' => ['effort' => 'low'], 'instructions' => "Atendés Artjet, el catálogo de Laboratorio Digital en Argentina. Artjet (sin guion) es el catálogo propio de Laboratorio Digital. Art-Jet (con guion) es una marca y casa central ajena: no pertenece a Laboratorio Digital, no es este catálogo y nunca debés presentarla como propia, asociada o parte del negocio. Asesorás solamente sobre los productos de la marca Art-Jet que Laboratorio Digital vende y que figuran en el catálogo provisto. Escribí como una persona que atiende el local: natural, cálida, directa y breve, sin presentarte como bot ni repetir saludos. También podés responder sobre horarios, formas de pago, ubicación y cómo comprar. Nunca inventes productos, usos, compatibilidades, precios, stock, horarios ni direcciones. Si falta un dato, decí que no lo tenés disponible. Artjet es solo un catálogo: nunca digas que se puede comprar o finalizar una compra ahí; derivá la compra a la tienda Laboratorio Digital o al local. Para temas ajenos, explicá brevemente en qué sí podés ayudar. Hacé como máximo una pregunta útil.\n\nCRITERIOS DE BÚSQUEDA:\n{$searchCriteria}\n\nCRITERIOS DE RESPUESTA:\n{$responseCriteria}\n\nDATOS DEL NEGOCIO:\n" . json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\nPRODUCTOS ART-JET QUE VENDE LABORATORIO DIGITAL:\n" . json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'input' => $input]);
        $text = '';
        foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $content) if (($content['type'] ?? '') === 'output_text') $text .= (string) ($content['text'] ?? '');
        if (trim($text) === '') throw new \RuntimeException('No pude responder en este momento.');
        return trim($text);
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function request(array $payload): array
    {
        $handle = curl_init(rtrim((string) $this->config['openai_base_url'], '/') . '/responses');
        if (!$handle) throw new \RuntimeException('No se pudo conectar con la atención online.');
        curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->config['openai_api_key'], 'Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), CURLOPT_TIMEOUT => 45]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $data = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) throw new \RuntimeException('No pude responder en este momento.');
        return $data;
    }
}
