<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class CatalogAiChatService
{
    public function __construct(
        private readonly array $config,
        private readonly CatalogAiToolService $tools,
        private readonly SettingsService $settings
    )
    {
    }

    /** @return array{connected:bool} */
    public function status(): array
    {
        return ['connected' => trim((string) ($this->config['openai_api_key'] ?? '')) !== ''];
    }

    /** @param list<array{role:string,content:string}> $history @return array<string,mixed> */
    public function reply(array $history): array
    {
        if (trim((string) ($this->config['openai_api_key'] ?? '')) === '') {
            throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Buscador IA.');
        }

        $interpretation = $this->structuredRequest(
            'interpretacion_catalogo',
            $this->interpretationSchema(),
            'Comprendé la necesidad del cliente antes de consultar cualquier catálogo. Usá conocimiento general para determinar qué quiere hacer, uso final, familia de producto, propiedades relevantes e incompatibilidades conceptuales. Conservá todo dato vigente de la conversación: una frase breve agrega o modifica una condición, no borra las anteriores. No inventes propiedades de productos del negocio. En core_product_term escribí solamente el sustantivo comercial central normalizado, en singular y sin explicaciones (por ejemplo, si piden una familia de productos, el nombre común de esa familia). Generá de una a cinco búsquedas razonables y progresivas para consultar después el catálogo: empezá por la intención más precisa y agregá alternativas conceptuales más amplias. Si el cliente da un código o SKU, conservalo en codigo. No copies necesariamente la frase literal. Tolerá singular/plural, acentos, errores leves, abreviaciones y marcas. Solo pedí una aclaración si un dato cambia sustancialmente la recomendación; si podés avanzar razonablemente, no preguntes.',
            $this->historyInput($history)
        );

        if (($interpretation['needs_clarification'] ?? false) === true) {
            return [
                'message' => trim((string) ($interpretation['question'] ?? '¿Podés contarme un poco más sobre el uso que le vas a dar?')),
                'raw_tools' => [],
                'display_results' => [],
                'interpretation' => $this->publicInterpretation($interpretation),
            ];
        }

        [$rows, $searchLog] = $this->searchCandidates($interpretation);
        $searchedGlobally = false;
        if ($rows === []) {
            $searchedGlobally = true;
            $globalSearches = $this->globalCatalogSearches($interpretation, $history);
            if ($globalSearches !== []) {
                [$rows, $globalLog] = $this->searchCandidates(['searches' => $globalSearches]);
                $searchLog[] = ['tool' => 'busquedaGlobal', 'arguments' => $globalSearches];
                $searchLog = [...$searchLog, ...$globalLog];
            }
        }
        $sizeGuide = $this->relevantSizeGuide($interpretation, $history, $rows);
        $evaluation = $this->structuredRequest(
            'evaluacion_catalogo',
            $this->evaluationSchema(),
            'Actuá como vendedor detrás del mostrador. Evaluá los candidatos reales contra la necesidad ya interpretada usando conocimiento general para decidir compatibilidad, pero tratá el catálogo suministrado como única fuente de verdad sobre nombre, descripción, categoría, variante, precio y stock. Clasificá cada candidato relevante como APTO, POSIBLE o NO_APTO. Un producto que comparte una palabra no es necesariamente recomendable: descartá como NO_APTO cualquier incompatibilidad de uso. Si el cliente pidió solamente una familia de producto sin imponer uso, material u otras condiciones, los productos cuyo nombre corresponde realmente a esa familia son APTO: no inventes requisitos ni digas que no están disponibles si el catálogo muestra stock. En ese caso seleccioná algunas variantes con stock como muestra y respondé que sí hay, aunque luego puedas hacer una pregunta breve para precisar. Seleccioná para mostrar únicamente variantes APTO que respondan a la intención actual; no mezcles accesorios, alternativas ni productos POSIBLE o NO_APTO. Si se suministra una tabla de talles, usala solo para responder consultas de medidas o talles y solo cuando corresponda al producto. Si falta un dato decisivo para recomendar, hacé una sola pregunta concreta, pero no ocultes la disponibilidad ya comprobada. Respondé breve y natural, sin explicar búsquedas ni usar frases como "Encontré", "la búsqueda devolvió" o "estos son los resultados". No inventes productos ni propiedades.',
            [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => json_encode([
                        'conversacion' => $history,
                        'necesidad_interpretada' => $interpretation,
                        'candidatos_catalogo' => $this->compactCandidates($rows),
                        'tabla_de_talles_relevante' => $sizeGuide,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                ]],
            ]]
        );

        $aptIds = [];
        foreach (($evaluation['classifications'] ?? []) as $classification) {
            if (is_array($classification) && ($classification['compatibility'] ?? '') === 'APTO') {
                $aptIds[(int) ($classification['variant_id'] ?? 0)] = true;
            }
        }
        $selectedIds = array_flip(array_map('intval', is_array($evaluation['selected_variant_ids'] ?? null) ? $evaluation['selected_variant_ids'] : []));
        $displayRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => isset($selectedIds[(int) $row['variante_id']], $aptIds[(int) $row['variante_id']])
        ));
        usort($displayRows, static fn (array $a, array $b): int =>
            (($b['stock'] > 0) <=> ($a['stock'] > 0))
            ?: strcmp((string) $a['producto'], (string) $b['producto'])
            ?: strcmp((string) $a['variante'], (string) $b['variante'])
        );

        return [
            'message' => trim((string) ($evaluation['message'] ?? 'No pude preparar una respuesta.')),
            'raw_tools' => $searchLog,
            'display_results' => array_slice($displayRows, 0, 12),
            'interpretation' => $this->publicInterpretation($interpretation) + [
                'origen_busqueda' => $searchedGlobally ? 'Catálogo + búsqueda global' : 'Catálogo',
            ],
        ];
    }

    /** @param list<array{role:string,content:string}> $history @return list<array<string,mixed>> */
    private function historyInput(array $history): array
    {
        return array_map(static function (array $message): array {
            $assistant = ($message['role'] ?? '') === 'assistant';
            return [
                'role' => $assistant ? 'assistant' : 'user',
                'content' => [[
                    'type' => $assistant ? 'output_text' : 'input_text',
                    'text' => (string) ($message['content'] ?? ''),
                ]],
            ];
        }, $history);
    }

    /** @param array<string,mixed> $interpretation @return array{0:list<array<string,mixed>>,1:list<array<string,mixed>>} */
    private function searchCandidates(array $interpretation): array
    {
        $rowsByVariant = [];
        $log = [];
        $searches = is_array($interpretation['searches'] ?? null) ? array_slice($interpretation['searches'], 0, 5) : [];
        $coreTerm = trim((string) ($interpretation['core_product_term'] ?? ''));
        if ($coreTerm !== '') array_unshift($searches, ['texto' => $coreTerm]);
        foreach ($searches as $filters) {
            if (!is_array($filters)) continue;
            $code = trim((string) ($filters['codigo'] ?? ''));
            unset($filters['codigo']);
            if ($code !== '') {
                $result = $this->tools->obtenerVariantesPorCodigo($code);
                $log[] = ['tool' => 'obtenerVariantes', 'arguments' => ['codigo' => $code], 'result' => $result];
                foreach ($result as $row) $rowsByVariant[(int) $row['variante_id']] = $row;
            }
            if (array_filter($filters, static fn ($value): bool => $value !== null && $value !== '') === []) continue;
            $result = $this->tools->buscarProductos($filters);
            $log[] = ['tool' => 'buscarProductos', 'arguments' => $filters, 'result' => $result];
            foreach ($result as $row) $rowsByVariant[(int) $row['variante_id']] = $row;
        }
        return [array_values($rowsByVariant), $log];
    }

    /** @param array<string,mixed> $interpretation @param list<array{role:string,content:string}> $history @return list<array<string,mixed>> */
    private function globalCatalogSearches(array $interpretation, array $history): array
    {
        try {
            $response = $this->request([
                'model' => 'gpt-5.6-terra',
                'store' => false,
                'reasoning' => ['effort' => 'low'],
                'tools' => [['type' => 'web_search_preview']],
                'instructions' => 'El catálogo local no tuvo coincidencias. Consultá la web solo para reconocer nombres comerciales, sinónimos o familias de productos que correspondan a la necesidad. Luego devolvé entre una y cinco búsquedas breves para contrastar exclusivamente contra el catálogo local. No recomiendes ni devuelvas comercios, enlaces, precios externos ni productos que no estén en el catálogo.',
                'input' => [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => json_encode(['conversacion' => $history, 'necesidad' => $interpretation], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]],
                ]],
                'text' => ['format' => ['type' => 'json_schema', 'name' => 'busquedas_catalogo', 'strict' => true, 'schema' => $this->globalSearchSchema()]],
            ]);
            $decoded = json_decode($this->outputText($response), true);
            return is_array($decoded['searches'] ?? null) ? array_slice($decoded['searches'], 0, 5) : [];
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return array<string,mixed> */
    private function globalSearchSchema(): array
    {
        return [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => ['searches' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => ['texto' => ['type' => 'string']], 'required' => ['texto']]]],
            'required' => ['searches'],
        ];
    }

    /** @param array<string,mixed> $interpretation @param list<array{role:string,content:string}> $history @param list<array<string,mixed>> $rows @return array<string,mixed>|null */
    private function relevantSizeGuide(array $interpretation, array $history, array $rows): ?array
    {
        $text = $this->fold(implode(' ', array_map(static fn (array $item): string => (string) ($item['content'] ?? ''), $history)) . ' ' . (string) ($interpretation['need'] ?? ''));
        if (!preg_match('/\b(talle|talles|medida|medidas|ancho|largo)\b/u', $text)) return null;
        $guide = $this->settings->sizeGuide();
        $names = $this->fold(implode(' ', array_map(static fn (array $row): string => (string) ($row['producto'] ?? ''), $rows)));
        $relevantRows = array_values(array_filter($guide['rows'], function (array $row) use ($names): bool {
            if ($names === '') return false;
            $terms = preg_split('/[^a-z0-9]+/', $this->fold((string) $row['group']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            return (bool) array_filter($terms, static fn (string $term): bool => strlen($term) >= 5 && str_contains($names, $term));
        }));
        return $relevantRows === [] ? null : ['intro' => $guide['intro'], 'rows' => array_slice($relevantRows, 0, 60)];
    }

    private function fold(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u']);
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function compactCandidates(array $rows): array
    {
        $products = [];
        foreach ($rows as $row) {
            $productId = (int) $row['producto_id'];
            $products[$productId] ??= [
                'producto_id' => $productId,
                'nombre' => $row['producto'],
                'descripcion' => $row['descripcion'],
                'categoria' => $row['categoria'],
                'variantes' => [],
            ];
            $products[$productId]['variantes'][] = [
                'variante_id' => (int) $row['variante_id'],
                'nombre' => $row['variante'],
                'precio' => $row['precio'],
                'stock' => $row['stock'],
            ];
        }
        return array_slice(array_values($products), 0, 60);
    }

    /** @param array<string,mixed> $interpretation @return array<string,mixed> */
    private function publicInterpretation(array $interpretation): array
    {
        return [
            'necesidad' => (string) ($interpretation['need'] ?? ''),
            'familia' => (string) ($interpretation['product_family'] ?? ''),
            'uso' => (string) ($interpretation['use'] ?? ''),
        ];
    }

    /** @param array<string,mixed> $schema @param list<array<string,mixed>> $input @return array<string,mixed> */
    private function structuredRequest(string $name, array $schema, string $instructions, array $input): array
    {
        $response = $this->request([
            'model' => 'gpt-5.6-terra',
            'store' => false,
            'reasoning' => ['effort' => 'low'],
            'instructions' => $instructions,
            'input' => $input,
            'text' => ['format' => ['type' => 'json_schema', 'name' => $name, 'strict' => true, 'schema' => $schema]],
        ]);
        $decoded = json_decode($this->outputText($response), true);
        if (!is_array($decoded)) throw new \RuntimeException('OpenAI no devolvió una respuesta estructurada válida.');
        return $decoded;
    }

    /** @return array<string,mixed> */
    private function interpretationSchema(): array
    {
        $filterProperties = [];
        foreach (['texto', 'marca', 'categoria', 'material', 'talle', 'color', 'tipo', 'uso', 'atributos', 'codigo'] as $field) {
            $filterProperties[$field] = ['type' => ['string', 'null']];
        }
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'need' => ['type' => 'string'],
                'product_family' => ['type' => 'string'],
                'core_product_term' => ['type' => 'string'],
                'use' => ['type' => 'string'],
                'relevant_factors' => ['type' => 'array', 'items' => ['type' => 'string']],
                'incompatibilities' => ['type' => 'array', 'items' => ['type' => 'string']],
                'needs_clarification' => ['type' => 'boolean'],
                'question' => ['type' => ['string', 'null']],
                'searches' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $filterProperties, 'required' => array_keys($filterProperties)]],
            ],
            'required' => ['need', 'product_family', 'core_product_term', 'use', 'relevant_factors', 'incompatibilities', 'needs_clarification', 'question', 'searches'],
        ];
    }

    /** @return array<string,mixed> */
    private function evaluationSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'message' => ['type' => 'string'],
                'classifications' => ['type' => 'array', 'items' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => [
                        'variant_id' => ['type' => 'integer'],
                        'compatibility' => ['type' => 'string', 'enum' => ['APTO', 'POSIBLE', 'NO_APTO']],
                        'reason' => ['type' => 'string'],
                    ],
                    'required' => ['variant_id', 'compatibility', 'reason'],
                ]],
                'selected_variant_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
            ],
            'required' => ['message', 'classifications', 'selected_variant_ids'],
        ];
    }

    /** @param array<string,mixed> $response */
    private function outputText(array $response): string
    {
        $text = '';
        foreach (($response['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                if (($content['type'] ?? '') === 'output_text') $text .= (string) ($content['text'] ?? '');
            }
        }
        return $text;
    }

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    private function request(array $payload): array
    {
        $handle = curl_init(rtrim((string) $this->config['openai_base_url'], '/') . '/responses');
        if (!$handle) throw new \RuntimeException('No se pudo conectar con OpenAI.');
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->config['openai_api_key'], 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            CURLOPT_TIMEOUT => 45,
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        $data = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($data)) {
            throw new \RuntimeException((string) ($data['error']['message'] ?? 'OpenAI no respondió.'));
        }
        return $data;
    }
}
