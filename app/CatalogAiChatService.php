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
            throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Vendedor IA.');
        }

        if ($this->isGreetingOnly($history)) {
            return [
                'message' => '¿Qué producto estás buscando?',
                'raw_tools' => [],
                'display_results' => [],
                'interpretation' => [],
            ];
        }

        $interpretation = $this->structuredRequest(
            'interpretacion_catalogo',
            $this->interpretationSchema(),
            'Comprendé la necesidad del cliente antes de consultar cualquier catálogo. Conservá datos previos solo si continúa con el mismo producto; detectá cambios y mantené separados los atributos de cada producto cuando el pedido es múltiple. Para bebé solo se ofrecen bodys y nunca se incluyen al pedir remeras. Si pide solamente remeras, son unisex. Para remeras sublimables, modal es la alternativa estándar; spum o jersey son secundarias y solo se consideran si se piden o no hay modal. En papel, el tamaño estándar es A4 y no hay papeles para impresoras láser. La letra G después de un número de papel indica gramaje: 200G es 200 gramos. Guardá ese dato en gramaje y buscá con el gramaje exacto; nunca lo confundas con la cantidad de hojas ni lo sustituyas por otro. En core_product_term escribí el sustantivo comercial central, normalizado y en singular. Generá búsquedas precisas y alternativas amplias para cada producto. Si hay código o SKU, conserválo en codigo. Tolerá plurales, acentos, errores leves, abreviaciones y marcas. Solo pedí una aclaración si cambia sustancialmente la recomendación.',
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

        $explicitRows = $this->explicitCatalogMatches($interpretation, $history);
        $similarRows = $explicitRows === [] && $this->hasExplicitVariantAttribute($history)
            ? $this->similarCatalogMatches($interpretation)
            : [];
        [$rows, $searchLog] = $this->searchCandidates($interpretation);
        $rowsByVariant = [];
        foreach ([...$rows, ...$explicitRows] as $row) {
            $rowsByVariant[(int) $row['variante_id']] = $row;
        }
        $rows = array_values($rowsByVariant);
        $rows = $this->applyBusinessRules($rows, $interpretation);
        $sizeGuide = $this->relevantSizeGuide($interpretation, $history, $rows);
        $evaluation = $this->structuredRequest(
            'evaluacion_catalogo',
            $this->evaluationSchema(),
            'Actuá como Vendedor IA detrás del mostrador. El catálogo suministrado es la única fuente de verdad sobre productos, precio y stock. Clasificá candidatos como APTO, POSIBLE o NO_APTO; seleccioná y mostrá únicamente los APTO. No confundas productos por palabras compartidas: los bodys nunca responden a remeras. Las remeras sin otra especificación son unisex. Para sublimables, modal es estándar; spum o jersey solo se proponen si se piden o no hay modal. Para papel el tamaño estándar es A4 y no hay opciones para impresora láser. La G después de un número de papel indica gramaje: 200G significa 200 gramos. El gramaje pedido es exacto: 180G no es APTO si se pidió 200G. No inventes propiedades ni disponibilidad. Cuando no se pidió otra condición, las variantes reales de esa familia con stock son APTAS. No ofrezcas colores alternativos salvo pedido expreso o falta de stock en negro/blanco. Usá tabla de talles solo si corresponde. La aplicación ya mostró el saludo inicial: no vuelvas a saludar ni a presentarte. Respondé breve, cálido y rioplatense; no repitas nombre, precio, talle ni stock porque aparecen aparte. Solo preguntá si falta un dato decisivo. No expliques la búsqueda ni uses "Encontré", "la búsqueda devolvió" o "estos son los resultados".',
            [[
                'role' => 'user',
                'content' => [[
                    'type' => 'input_text',
                    'text' => json_encode([
                        'conversacion' => $history,
                        'necesidad_interpretada' => $interpretation,
                        'candidatos_catalogo' => $this->compactCandidates($rows),
                        'tabla_de_talles_relevante' => $sizeGuide,
                        'reglas_de_saludo' => 'El saludo inicial ya está en la conversación. Si el cliente escribe solo un saludo, no respondas con otro saludo ni presentación: preguntá qué producto busca. Si su nombre está disponible de forma confiable en la conversación, podés usarlo una sola vez con buen día, buenas tardes o buenas noches según la hora local argentina indicada.',
                        'hora_local_argentina' => (new \DateTimeImmutable('now', new \DateTimeZone('America/Argentina/Buenos_Aires')))->format('H:i'),
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
        $usedExplicitFallback = false;
        if ($displayRows === [] && $explicitRows !== []) {
            $displayRows = array_values(array_filter(
                $explicitRows,
                static fn (array $row): bool => $row['stock'] === null || (int) $row['stock'] > 0
            ));
            $usedExplicitFallback = $displayRows !== [];
        }
        $usedSimilarFallback = false;
        if ($displayRows === [] && $similarRows !== []) {
            $displayRows = $similarRows;
            $usedSimilarFallback = true;
        }
        usort($displayRows, static fn (array $a, array $b): int =>
            (($b['stock'] > 0) <=> ($a['stock'] > 0))
            ?: strcmp((string) $a['producto'], (string) $b['producto'])
            ?: strcmp((string) $a['variante'], (string) $b['variante'])
        );

        $message = $usedExplicitFallback
                ? 'Sí, hay opciones disponibles que coinciden con lo que pediste.'
                : ($usedSimilarFallback
                    ? 'No hay una coincidencia exacta disponible; estas son las opciones más parecidas que sí tenemos.'
                    : trim((string) ($evaluation['message'] ?? 'No pude preparar una respuesta.')));
        $continuation = $this->continuationSuggestion($displayRows);

        return [
            'message' => trim($message . ($continuation === '' ? '' : ' ' . $continuation)),
            'raw_tools' => $searchLog,
            'display_results' => array_slice($displayRows, 0, 12),
            'interpretation' => $this->publicInterpretation($interpretation) + [
                'origen_busqueda' => 'Catálogo',
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
        $productRequests = is_array($interpretation['product_requests'] ?? null) ? array_slice($interpretation['product_requests'], 0, 4) : [];
        foreach (array_reverse($productRequests) as $request) {
            if (!is_array($request) || trim((string) ($request['product_term'] ?? '')) === '') continue;
            $filters = ['texto' => trim((string) $request['product_term'])];
            foreach (['talle', 'color', 'material', 'gramaje'] as $field) {
                if (($request[$field] ?? null) !== null && trim((string) $request[$field]) !== '') {
                    $filters[$field] = trim((string) $request[$field]);
                }
            }
            array_unshift($searches, $filters);
        }
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
    private function explicitCatalogMatches(array $interpretation, array $history): array
    {
        $lastMessage = '';
        foreach (array_reverse($history) as $message) {
            if (($message['role'] ?? '') === 'user') {
                $lastMessage = (string) ($message['content'] ?? '');
                break;
            }
        }
        $filters = ['texto' => trim((string) ($interpretation['core_product_term'] ?? ''))];
        if (preg_match('/\btalle\s*([[:alnum:].-]+)/iu', $lastMessage, $size)) {
            $filters['talle'] = $size[1];
        }
        $colors = ['negro', 'negra', 'blanco', 'blanca', 'rojo', 'roja', 'azul', 'verde', 'gris', 'rosa', 'amarillo', 'amarilla', 'violeta', 'naranja', 'beige', 'marron', 'marrón'];
        foreach ($colors as $color) {
            if (preg_match('/\b' . preg_quote($color, '/') . '\b/iu', $lastMessage)) {
                $filters['color'] = $color;
                break;
            }
        }
        return $filters['texto'] === '' ? [] : $this->tools->buscarProductos($filters);
    }

    /** @param list<array{role:string,content:string}> $history */
    private function hasExplicitVariantAttribute(array $history): bool
    {
        $text = implode(' ', array_map(static fn (array $message): string => (string) ($message['content'] ?? ''), $history));
        return (bool) preg_match('/\btalle\s*[[:alnum:].-]+\b|\b(negro|negra|blanco|blanca|rojo|roja|azul|verde|gris|rosa|amarillo|amarilla|violeta|naranja|beige|marron|marrón)\b/iu', $text);
    }

    /** @param array<string,mixed> $interpretation @return list<array<string,mixed>> */
    private function similarCatalogMatches(array $interpretation): array
    {
        $term = trim((string) ($interpretation['core_product_term'] ?? ''));
        if ($term === '') return [];
        return array_slice(array_values(array_filter(
            $this->tools->buscarProductos(['texto' => $term]),
            static fn (array $row): bool => $row['stock'] === null || (int) $row['stock'] > 0
        )), 0, 12);
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

    /** @param list<array{role:string,content:string}> $history */
    private function isGreetingOnly(array $history): bool
    {
        foreach (array_reverse($history) as $message) {
            if (($message['role'] ?? '') !== 'user') continue;
            return (bool) preg_match('/^\s*(hola|buenas|buen dia|buen día|buenas tardes|buenas noches)[!.?\s]*$/iu', (string) ($message['content'] ?? ''));
        }
        return false;
    }

    /** @param list<array<string,mixed>> $rows @param array<string,mixed> $interpretation @return list<array<string,mixed>> */
    private function applyBusinessRules(array $rows, array $interpretation): array
    {
        $need = $this->fold(implode(' ', [
            (string) ($interpretation['need'] ?? ''),
            (string) ($interpretation['product_family'] ?? ''),
            (string) ($interpretation['core_product_term'] ?? ''),
        ]));
        $paperGramajes = [];
        foreach ((array) ($interpretation['product_requests'] ?? []) as $request) {
            if (!is_array($request) || !str_contains($this->fold((string) ($request['product_term'] ?? '')), 'papel')) continue;
            if (preg_match('/\d+/', (string) ($request['gramaje'] ?? ''), $match)) $paperGramajes[] = $match[0];
        }
        return array_values(array_filter($rows, function (array $row) use ($need, $paperGramajes): bool {
            $product = $this->fold((string) ($row['producto'] ?? ''));
            $isBody = (bool) preg_match('/\bbody(s)?\b/u', $product);
            if (str_contains($need, 'remera') && $isBody) return false;
            if (str_contains($need, 'bebe') && !$isBody) return false;
            if (str_contains($need, 'papel') && str_contains($this->fold((string) ($row['producto'] ?? '') . ' ' . (string) ($row['descripcion'] ?? '')), 'laser')) return false;
            if ($paperGramajes !== [] && str_contains($product, 'papel')) {
                $details = $this->fold((string) ($row['producto'] ?? '') . ' ' . (string) ($row['descripcion'] ?? ''));
                if (!array_filter($paperGramajes, static fn (string $gramaje): bool => (bool) preg_match('/\b' . preg_quote($gramaje, '/') . '\s*g\b/u', $details))) return false;
            }
            return true;
        }));
    }

    /** @param list<array<string,mixed>> $rows */
    private function continuationSuggestion(array $rows): string
    {
        if ($rows === []) return 'Si querés, puedo buscar otro producto del catálogo.';
        $sizes = array_filter(array_unique(array_map(static fn (array $row): string => trim((string) ($row['talle'] ?? '')), $rows)));
        $colors = array_filter(array_unique(array_map(static fn (array $row): string => trim((string) ($row['color'] ?? '')), $rows)));
        if ($sizes !== [] && $colors !== []) return '¿Querés que revisemos otros talles de estas mismas opciones?';
        if ($sizes !== []) return '¿Querés que revisemos otros talles de estas mismas opciones?';
        return 'Si querés, puedo comparar estas opciones o buscar otro producto.';
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
        foreach (['texto', 'marca', 'categoria', 'material', 'talle', 'color', 'gramaje', 'tipo', 'uso', 'atributos', 'codigo'] as $field) {
            $filterProperties[$field] = ['type' => ['string', 'null']];
        }
        $requestProperties = [
            'product_term' => ['type' => 'string'],
            'talle' => ['type' => ['string', 'null']],
            'color' => ['type' => ['string', 'null']],
            'material' => ['type' => ['string', 'null']],
            'gramaje' => ['type' => ['string', 'null']],
        ];
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'need' => ['type' => 'string'],
                'product_family' => ['type' => 'string'],
                'core_product_term' => ['type' => 'string'],
                'mode' => ['type' => 'string', 'enum' => ['new', 'continued', 'changed', 'multiple']],
                'product_requests' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $requestProperties, 'required' => array_keys($requestProperties)]],
                'use' => ['type' => 'string'],
                'relevant_factors' => ['type' => 'array', 'items' => ['type' => 'string']],
                'incompatibilities' => ['type' => 'array', 'items' => ['type' => 'string']],
                'needs_clarification' => ['type' => 'boolean'],
                'question' => ['type' => ['string', 'null']],
                'searches' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => false, 'properties' => $filterProperties, 'required' => array_keys($filterProperties)]],
            ],
            'required' => ['need', 'product_family', 'core_product_term', 'mode', 'product_requests', 'use', 'relevant_factors', 'incompatibilities', 'needs_clarification', 'question', 'searches'],
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
