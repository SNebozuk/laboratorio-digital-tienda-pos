<?php
declare(strict_types=1);

namespace LaboratorioDigital;

final class CatalogAiChatService
{
    public function __construct(
        private readonly array $config,
        private readonly CatalogAiToolService $tools,
        private readonly SettingsService $settings,
        private readonly TutorialService $tutorials
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
            throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Asesor IA.');
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
            'Comprendé la solicitud activa del cliente y generá búsquedas precisas para consultar exclusivamente el catálogo local. Completá todos los campos del esquema y pedí más información si no podés determinar si continúa con el producto anterior o cambió de producto.' . $this->criteriaInstructions('search'),
            $this->historyInput($history)
        );

        if (($interpretation['needs_clarification'] ?? false) === true) {
            $fallback = trim((string) ($interpretation['question'] ?? '¿Podés contarme un poco más sobre el uso que le vas a dar?'));
            return [
                'message' => $this->composeResponse($history, $interpretation, [], ['estado' => 'necesita_aclaracion'], $fallback),
                'raw_tools' => [],
                'display_results' => [],
                'interpretation' => $this->publicInterpretation($interpretation),
            ];
        }

        $explicitRows = $this->explicitCatalogMatches($interpretation, $history);
        [$rows, $searchLog] = $this->searchCandidates($interpretation);
        $rowsByVariant = [];
        foreach ([...$rows, ...$explicitRows] as $row) {
            $rowsByVariant[(int) $row['variante_id']] = $row;
        }
        $rows = array_values($rowsByVariant);
        $productTerm = (string) ($interpretation['core_product_term'] ?? '');
        $exactRows = $this->exactRequestedRows($rows, $interpretation, $history);
        $stockAlternatives = false;
        $missingSizeAlternatives = false;
        $exactOutOfStock = false;
        if ($exactRows !== []) {
            $rows = $exactRows;
            $visibleExactRows = array_values(array_filter($exactRows, static fn (array $row): bool => ($row['visible'] ?? true)));
            $availableExactRows = array_values(array_filter($visibleExactRows, static fn (array $row): bool => $row['stock'] === null || (int) $row['stock'] > 0));
            if ($visibleExactRows !== [] && $availableExactRows === []) {
                $exactOutOfStock = true;
                $alternativePool = $this->tools->buscarProductos(['texto' => $productTerm], 200);
                $alternativePool = $this->exactRequestedRows($alternativePool, $interpretation, $history, false, false);
                $alternativePool = array_values(array_filter($alternativePool, static fn (array $row): bool => ($row['visible'] ?? true) && ($row['stock'] === null || (int) $row['stock'] > 0)));
                $rows = $this->nearestAvailableSizes($visibleExactRows, $alternativePool);
                $stockAlternatives = $rows !== [];
            }
        } else {
            $candidateRows = $rows;
            $requestedSize = $this->requestedSize($interpretation, $history);
            $alternativePool = $requestedSize === null ? [] : $this->tools->buscarProductos(['texto' => $productTerm], 200);
            $alternativePool = $this->exactRequestedRows($alternativePool, $interpretation, $history, false, false);
            $alternativePool = array_values(array_filter($alternativePool, static fn (array $row): bool => ($row['visible'] ?? true) && ($row['stock'] === null || (int) $row['stock'] > 0)));
            $rows = $requestedSize === null ? [] : $this->nearestNumericSizes($alternativePool, $requestedSize, $this->requestedGarmentLine($interpretation, $history));
            $missingSizeAlternatives = $rows !== [];
            if (!$missingSizeAlternatives && ($suggestedTerm = $this->approximateProductTerm($candidateRows, $productTerm)) !== null) {
                $fallback = '¿Quisiste decir "' . $suggestedTerm . '"?';
                return [
                    'message' => $this->composeResponse($history, $interpretation, [], ['estado' => 'posible_error', 'termino_sugerido' => $suggestedTerm], $fallback),
                    'raw_tools' => [],
                    'display_results' => [],
                    'interpretation' => $this->publicInterpretation($interpretation) + [
                        'origen_busqueda' => 'Catálogo',
                    ],
                ];
            }
        }
        $displayRows = array_values(array_filter($rows, static fn (array $row): bool => ($row['visible'] ?? true) && ($row['stock'] === null || (int) $row['stock'] > 0)));
        $displayRows = $this->withSizeMeasurements($displayRows);
        usort($displayRows, static fn (array $a, array $b): int =>
            (($b['stock'] > 0) <=> ($a['stock'] > 0))
            ?: strcmp((string) $a['producto'], (string) $b['producto'])
            ?: strcmp((string) $a['variante'], (string) $b['variante'])
        );

        $criteria = [];
        if (count(array_unique(array_filter(array_map(static fn (array $row): string => trim((string) ($row['talle'] ?? '')), $displayRows)))) > 1) $criteria[] = 'talle';
        if (count(array_unique(array_filter(array_map(static fn (array $row): string => trim((string) ($row['color'] ?? '')), $displayRows)))) > 1) $criteria[] = 'color';
        if (count($displayRows) > 24) $criteria[] = 'material o uso';
        $clarification = $this->productClarificationQuestion($displayRows);
        $fallbackMessage = $stockAlternatives
            ? 'No tengo stock del talle solicitado. Te muestro los talles disponibles más cercanos, uno inferior y otro siguiente cuando existen. También podés consultar las medidas de cada opción.'
            : ($missingSizeAlternatives
                ? 'Ese talle no figura en estas opciones. Te muestro el talle inferior y el superior más cercanos que existen y tienen stock. También podés consultar sus medidas.'
            : ($exactOutOfStock
                ? 'La opción exacta existe, pero no tiene stock y no encontré talles cercanos disponibles.'
                : ($clarification ?? ($displayRows === []
            ? 'No encontré una coincidencia exacta con esos datos. ¿Podés confirmar el producto y la variante que buscás?'
            : (count($displayRows) > 24
                ? 'Hay más de 24 opciones disponibles. Para acotar mejor, ¿preferís filtrar por ' . implode(', ', $criteria ?: ['tipo de producto']) . '?'
                : 'Estas son las opciones disponibles en Laboratorio Digital.')))));
        $continuation = $clarification === null && !$stockAlternatives && !$missingSizeAlternatives ? $this->continuationSuggestion($displayRows) : '';
        $fallbackMessage = trim($fallbackMessage . ($continuation === '' ? '' : ' ' . $continuation));
        $message = $this->composeResponse($history, $interpretation, array_slice($displayRows, 0, 24), [
            'estado' => $stockAlternatives ? 'alternativas_por_falta_de_stock' : ($missingSizeAlternatives ? 'alternativas_por_talle_inexistente' : ($exactOutOfStock ? 'sin_stock_sin_alternativas' : ($displayRows === [] ? 'sin_coincidencias' : 'resultados'))),
            'pregunta_de_aclaracion' => $clarification,
            'cantidad_resultados' => count($displayRows),
        ], $fallbackMessage);

        return [
            'message' => $message,
            'raw_tools' => $searchLog,
            'display_results' => array_slice($displayRows, 0, 24),
            'needs_clarification' => $displayRows === [] && !$exactOutOfStock,
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
            foreach (['talle', 'color', 'material', 'gramaje', 'tamano'] as $field) {
                if (($request[$field] ?? null) !== null && trim((string) $request[$field]) !== '') {
                    $filters[$field] = trim((string) $request[$field]);
                }
            }
            if (($request['tipo_papel'] ?? null) !== null && trim((string) $request['tipo_papel']) !== '') $filters['tipo'] = trim((string) $request['tipo_papel']);
            array_unshift($searches, $filters);
        }
        $coreTerm = trim((string) ($interpretation['core_product_term'] ?? ''));
        if ($coreTerm !== '') array_unshift($searches, ['texto' => $coreTerm]);
        $seenSearches = [];
        foreach ($searches as $filters) {
            if (!is_array($filters)) continue;
            $code = trim((string) ($filters['codigo'] ?? ''));
            unset($filters['codigo']);
            $searchKey = json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (isset($seenSearches[$searchKey])) continue;
            $seenSearches[$searchKey] = true;
            if ($code !== '') {
                $result = $this->tools->obtenerVariantesPorCodigo($code);
                $log[] = ['tool' => 'obtenerVariantes', 'arguments' => ['codigo' => $code], 'result_count' => count($result)];
                foreach ($result as $row) $rowsByVariant[(int) $row['variante_id']] = $row;
            }
            if (array_filter($filters, static fn ($value): bool => $value !== null && $value !== '') === []) continue;
            $result = $this->tools->buscarProductos($filters);
            $log[] = ['tool' => 'buscarProductos', 'arguments' => $filters, 'result_count' => count($result)];
            foreach ($result as $row) $rowsByVariant[(int) $row['variante_id']] = $row;
        }
        return [array_values($rowsByVariant), $log];
    }

    /** @param array<string,mixed> $interpretation @param list<array{role:string,content:string}> $history @return list<array<string,mixed>> */
    private function explicitCatalogMatches(array $interpretation, array $history): array
    {
        $attributes = $this->explicitAttributes($history);
        $filters = ['texto' => trim((string) ($interpretation['core_product_term'] ?? ''))];
        if (isset($attributes['talle'])) $filters['talle'] = $attributes['talle'];
        if (isset($attributes['color'])) $filters['color'] = $attributes['color'];
        return $filters['texto'] === '' ? [] : $this->tools->buscarProductos($filters);
    }

    /** @param list<array{role:string,content:string}> $history @return array<string,string> */
    private function explicitAttributes(array $history): array
    {
        $text = '';
        foreach (array_reverse($history) as $message) {
            if (($message['role'] ?? '') === 'user') { $text = $this->fold((string) ($message['content'] ?? '')); break; }
        }
        $attributes = [];
        if (preg_match('/\btalle\s*([[:alnum:].-]+)/u', $text, $size)) $attributes['talle'] = $size[1];
        if (preg_match('/\b(nino|nina|infantil)\b/u', $text)) $attributes['linea_remera'] = 'nino';
        elseif (preg_match('/\b(mujer|dama)\b/u', $text)) $attributes['linea_remera'] = 'mujer';
        elseif (preg_match('/\b(unisex|adulto|adulta)\b/u', $text)) $attributes['linea_remera'] = 'unisex';
        $colors = [
            'negro' => ['negro', 'negra', 'negros', 'negras'], 'blanco' => ['blanco', 'blanca', 'blancos', 'blancas'],
            'rojo' => ['rojo', 'roja', 'rojos', 'rojas'], 'azul' => ['azul', 'azules'], 'verde' => ['verde', 'verdes'],
            'gris' => ['gris', 'grises'], 'rosa' => ['rosa', 'rosas'], 'amarillo' => ['amarillo', 'amarilla', 'amarillos', 'amarillas'],
            'violeta' => ['violeta', 'violetas'], 'naranja' => ['naranja', 'naranjas'], 'beige' => ['beige', 'beiges'], 'marron' => ['marron', 'marrones'],
        ];
        $words = preg_split('/[^a-z0-9]+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $word) foreach ($colors as $canonical => $aliases) foreach ($aliases as $alias) {
            if ($word === $alias || (strlen($word) >= 4 && levenshtein($this->singular($word), $this->singular($alias)) === 1)) {
                $attributes['color'] = $canonical;
                break 3;
            }
        }
        return $attributes;
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
            if ($names === '') return true;
            $terms = preg_split('/[^a-z0-9]+/', $this->fold((string) $row['group']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            return (bool) array_filter($terms, static fn (string $term): bool => strlen($term) >= 5 && str_contains($names, $term));
        }));
        return $relevantRows === [] ? null : ['intro' => $guide['intro'], 'rows' => array_slice($relevantRows, 0, 60)];
    }

    private function fold(string $value): string
    {
        $value = function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        return strtr($value, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n', 'Ñ' => 'n']);
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

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function exactRequestedRows(array $rows, array $interpretation, array $history, bool $includeSize = true, bool $includeGarmentLine = true): array
    {
        $requests = array_values(array_filter((array) ($interpretation['product_requests'] ?? []), static fn (mixed $request): bool => is_array($request) && trim((string) ($request['product_term'] ?? '')) !== ''));
        if ($requests === []) $requests = [['product_term' => (string) ($interpretation['core_product_term'] ?? '')]];
        if (($interpretation['mode'] ?? '') !== 'multiple' && count($requests) > 1) $requests = [end($requests)];
        $explicit = $this->explicitAttributes($history);
        if (!isset($explicit['linea_remera']) && ($interpretation['mode'] ?? '') === 'continued') {
            $line = $this->previousGarmentLine($history);
            if ($line !== null) $explicit['linea_remera'] = $line;
        }

        $exact = [];
        foreach ($requests as $request) {
            if (isset($explicit['talle'])) $request['talle'] = $explicit['talle'];
            if (isset($explicit['color'])) $request['color'] = $explicit['color'];
            $productRequestTerm = (string) $request['product_term'];
            if (!$includeGarmentLine) $productRequestTerm = (string) preg_replace('/\b(niño|niña|nino|nina|infantil|mujer|dama|unisex|adulto|adulta)\b/iu', '', $productRequestTerm);
            foreach ($rows as $row) {
                $identity = $this->fold((string) ($row['producto'] ?? '') . ' ' . (string) ($row['variante'] ?? ''));
                $details = $identity . ' ' . $this->fold((string) ($row['descripcion'] ?? ''));
                if ($includeGarmentLine && isset($explicit['linea_remera']) && str_contains($identity, 'remera') && $this->garmentLine($identity) !== $explicit['linea_remera']) continue;
                if (!$this->exactTextMatch($identity, $productRequestTerm)) continue;
                if (!$this->exactTextMatch($identity, (string) ($request['material'] ?? ''))) continue;
                if (!$this->exactTextMatch($identity, (string) ($request['color'] ?? ''))) continue;
                if ($includeSize && ($size = trim((string) ($request['talle'] ?? ''))) !== '' && $this->fold((string) ($row['talle'] ?? '')) !== $this->fold($size)) continue;
                if (str_contains($this->fold((string) $request['product_term']), 'papel')) {
                    if (!$this->exactTextMatch($details, (string) ($request['tamano'] ?? ''))) continue;
                    if (!$this->exactTextMatch($details, (string) ($request['tipo_papel'] ?? ''))) continue;
                    if (!$this->exactGramajeMatch($details, (string) ($request['gramaje'] ?? ''))) continue;
                }
                $exact[(int) $row['variante_id']] = $row;
            }
        }
        return array_values($exact);
    }

    /** @param list<array<string,mixed>> $requestedRows @param list<array<string,mixed>> $availableRows @return list<array<string,mixed>> */
    private function nearestAvailableSizes(array $requestedRows, array $availableRows): array
    {
        $selected = [];
        foreach ($requestedRows as $requestedRow) {
            $requestedRank = $this->sizeRank($requestedRow);
            if ($requestedRank === null) continue;
            $lowerRank = null;
            $upperRank = null;
            foreach ($availableRows as $row) {
                if (!$this->sameSizeLine($requestedRow, $row)) continue;
                $rank = $this->relativeSizeRank($requestedRow, $row);
                if ($rank === null || $rank === $requestedRank) continue;
                if ($rank < $requestedRank && ($lowerRank === null || $rank > $lowerRank)) $lowerRank = $rank;
                if ($rank > $requestedRank && ($upperRank === null || $rank < $upperRank)) $upperRank = $rank;
            }
            foreach ($availableRows as $row) {
                if (!$this->sameSizeLine($requestedRow, $row)) continue;
                $rank = $this->relativeSizeRank($requestedRow, $row);
                if ($rank !== null && ($rank === $lowerRank || $rank === $upperRank)) $selected[(int) $row['variante_id']] = $row;
            }
        }
        return array_values($selected);
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function nearestNumericSizes(array $rows, string $requestedSize, ?string $requestedLine): array
    {
        if (!is_numeric($requestedSize)) return [];
        $requested = (float) $requestedSize;
        $lower = null;
        $upper = null;
        foreach ($rows as $row) {
            $size = (string) ($row['talle'] ?? '');
            if (!is_numeric($size)) continue;
            $line = $this->garmentLine($this->fold((string) ($row['producto'] ?? '')));
            if ($requestedLine !== null && $line !== $requestedLine) continue;
            $numeric = (float) $size;
            if ($numeric < $requested && ($lower === null || $numeric > $lower)) $lower = $numeric;
            if ($numeric > $requested && ($upper === null || $numeric < $upper)) $upper = $numeric;
        }
        return array_values(array_filter($rows, function (array $row) use ($lower, $upper, $requestedLine, $requestedSize): bool {
            $size = (string) ($row['talle'] ?? '');
            if (!is_numeric($size)) return false;
            $line = $this->garmentLine($this->fold((string) ($row['producto'] ?? '')));
            if ($requestedLine !== null && $line !== $requestedLine) return false;
            $numeric = (float) $size;
            return $numeric === $lower || $numeric === $upper;
        }));
    }

    /** @param array<string,mixed> $interpretation @param list<array{role:string,content:string}> $history */
    private function requestedSize(array $interpretation, array $history): ?string
    {
        $explicit = $this->explicitAttributes($history);
        if (isset($explicit['talle'])) return $explicit['talle'];
        $requests = array_values(array_filter((array) ($interpretation['product_requests'] ?? []), 'is_array'));
        if ($requests === []) return null;
        $request = end($requests);
        $size = trim((string) ($request['talle'] ?? ''));
        return $size === '' ? null : $size;
    }

    /** @param array<string,mixed> $interpretation @param list<array{role:string,content:string}> $history */
    private function requestedGarmentLine(array $interpretation, array $history): ?string
    {
        $explicit = $this->explicitAttributes($history);
        if (isset($explicit['linea_remera'])) return $explicit['linea_remera'];
        return ($interpretation['mode'] ?? '') === 'continued' ? $this->previousGarmentLine($history) : null;
    }

    /** @param array<string,mixed> $requested @param array<string,mixed> $candidate */
    private function sameSizeLine(array $requested, array $candidate): bool
    {
        $requestedName = $this->fold((string) ($requested['producto'] ?? ''));
        $candidateName = $this->fold((string) ($candidate['producto'] ?? ''));
        if (str_contains($requestedName, 'remera') && str_contains($candidateName, 'remera')) {
            $requestedLine = $this->garmentLine($requestedName);
            $candidateLine = $this->garmentLine($candidateName);
            return $requestedLine === $candidateLine;
        }
        return (int) ($requested['producto_id'] ?? 0) === (int) ($candidate['producto_id'] ?? -1);
    }

    private function garmentLine(string $name): string
    {
        if (preg_match('/\b(nino|nina|infantil)\b/u', $name)) return 'nino';
        if (preg_match('/\b(mujer|dama)\b/u', $name)) return 'mujer';
        return 'unisex';
    }

    /** @param list<array{role:string,content:string}> $history */
    private function previousGarmentLine(array $history): ?string
    {
        foreach (array_reverse($history) as $message) {
            if (($message['role'] ?? '') !== 'user') continue;
            $text = $this->fold((string) ($message['content'] ?? ''));
            if (preg_match('/\b(nino|nina|infantil)\b/u', $text)) return 'nino';
            if (preg_match('/\b(mujer|dama)\b/u', $text)) return 'mujer';
            if (preg_match('/\b(unisex|adulto|adulta)\b/u', $text)) return 'unisex';
        }
        return null;
    }

    /** @param array<string,mixed> $row */
    private function sizeRank(array $row): ?float
    {
        $size = trim((string) ($row['talle'] ?? ''));
        if (!is_numeric($size)) return null;
        return (float) $size;
    }

    /** @param array<string,mixed> $requested @param array<string,mixed> $candidate */
    private function relativeSizeRank(array $requested, array $candidate): ?float
    {
        return $this->sizeRank($candidate);
    }

    /** @param list<array<string,mixed>> $rows @return list<array<string,mixed>> */
    private function withSizeMeasurements(array $rows): array
    {
        $guide = $this->settings->sizeGuide();
        foreach ($rows as &$row) {
            $best = null;
            $bestScore = -1;
            $product = $this->fold((string) ($row['producto'] ?? ''));
            foreach ($guide['rows'] as $measure) {
                if ($this->fold((string) ($measure['size'] ?? '')) !== $this->fold((string) ($row['talle'] ?? ''))) continue;
                $group = $this->fold((string) ($measure['group'] ?? ''));
                if (!$this->measurementGroupMatches($product, $group)) continue;
                $tokens = preg_split('/[^a-z0-9.]+/', $group, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $score = count(array_filter($tokens, static fn (string $token): bool => strlen($token) >= 4 && str_contains($product, $token)));
                if ($score > $bestScore) { $best = $measure; $bestScore = $score; }
            }
            if ($best !== null) {
                $row['medidas'] = trim('Ancho ' . (string) ($best['width'] ?? '') . ' · Largo ' . (string) ($best['length'] ?? '') . ((string) ($best['note'] ?? '') !== '' ? ' · ' . (string) $best['note'] : ''));
            }
        }
        unset($row);
        return $rows;
    }

    private function measurementGroupMatches(string $product, string $group): bool
    {
        foreach (['remera', 'body', 'buzo', 'campera'] as $type) {
            if (str_contains($product, $type) && !str_contains($group, $type)) return false;
        }
        foreach ([['nino', 'nino'], ['infantil', 'nino'], ['mujer', 'mujer'], ['dama', 'mujer']] as [$productTerm, $groupTerm]) {
            if (str_contains($product, $productTerm) && !str_contains($group, $groupTerm)) return false;
        }
        if (!str_contains($product, 'nino') && !str_contains($product, 'infantil') && !str_contains($product, 'mujer') && !str_contains($product, 'dama') && str_contains($product, 'remera') && !str_contains($group, 'unisex')) return false;
        if (str_contains($product, 'modal') && !str_contains($group, 'modal')) return false;
        if (str_contains($product, 'algodon') && !str_contains($group, 'algodon')) return false;
        if ((str_contains($group, '24.1') || str_contains($group, '20.1')) && str_contains($product, '24.1') && !str_contains($group, '24.1')) return false;
        if ((str_contains($group, '24.1') || str_contains($group, '20.1')) && str_contains($product, '20.1') && !str_contains($group, '20.1')) return false;
        return true;
    }

    private function exactTextMatch(string $value, string $term): bool
    {
        $term = $this->fold(trim($term));
        if ($term === '') return true;
        $words = preg_split('/\\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) return true;
        $words = array_values(array_diff($words, ['de', 'del', 'para']));
        foreach ($words as $word) {
            $pattern = '/(?<![a-z0-9])' . preg_quote($this->singular($word), '/') . '(?:s|es)?(?![a-z0-9])/u';
            if (!preg_match($pattern, $value)) return false;
        }
        return true;
    }

    private function exactGramajeMatch(string $value, string $gramaje): bool
    {
        if (!preg_match('/\\d+/', $gramaje, $match)) return true;
        return (bool) preg_match('/\\b' . preg_quote($match[0], '/') . '\\s*g?\\b/u', $value);
    }

    /** @param list<array<string,mixed>> $rows */
    private function productClarificationQuestion(array $rows): ?string
    {
        $products = array_values(array_unique(array_filter(array_map(static fn (array $row): string => trim((string) ($row['producto'] ?? '')), $rows))));
        if (count($products) < 2) return null;

        $labels = [];
        foreach ($products as $product) {
            $name = $this->fold($product);
            if (str_contains($name, 'unisex')) $labels['unisex'] = 'unisex';
            elseif (preg_match('/\\b(nino|nina|infantil)\\b/u', $name)) $labels['niño'] = 'de niño';
            elseif (str_contains($name, 'mujer') || str_contains($name, 'dama')) $labels['mujer'] = 'de mujer';
        }
        if (count($labels) >= 2) return 'Encontré opciones ' . implode(', ', array_values($labels)) . ' que coinciden. ¿Cuál buscás?';
        return 'Encontré varias opciones que coinciden. ¿Cuál buscás? Podés elegir una en la tabla.';
    }

    /** @param list<array<string,mixed>> $rows */
    private function approximateProductTerm(array $rows, string $term): ?string
    {
        $term = $this->singular($this->fold(trim($term)));
        if (strlen($term) < 4 || str_contains($term, ' ')) return null;

        foreach ($rows as $row) {
            $product = $this->fold((string) ($row['producto'] ?? ''));
            $words = preg_split('/[^a-z0-9]+/u', $product, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($words as $word) {
                $candidate = $this->singular($word);
                $distance = levenshtein($term, $candidate);
                if (strlen($candidate) >= 4 && $distance > 0 && $distance <= 1) return $word;
            }
        }
        return null;
    }

    private function singular(string $value): string
    {
        return strlen($value) > 4 ? (string) preg_replace('/s$/', '', $value) : $value;
    }

    /** @param list<array<string,mixed>> $rows */
    private function continuationSuggestion(array $rows): string
    {
        if ($rows === []) return 'Si querés, puedo buscar otro producto del catálogo.';
        $sizes = array_filter(array_unique(array_map(static fn (array $row): string => trim((string) ($row['talle'] ?? '')), $rows)));
        $colors = array_filter(array_unique(array_map(static fn (array $row): string => trim((string) ($row['color'] ?? '')), $rows)));
        if ($sizes !== [] && $colors !== []) return '¿Querés agregar alguna al carrito? Elegí una opción y decime cuántas unidades necesitás.';
        if ($sizes !== []) return '¿Querés agregar alguna al carrito? Elegí una opción y decime cuántas unidades necesitás.';
        return '¿Querés agregar alguna al carrito? Elegí una opción y decime cuántas unidades necesitás.';
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

    private function criteriaInstructions(string $type): string
    {
        $criteria = $this->settings->aiCriteria()[$type] ?? [];
        if ($criteria === []) return '';
        return "\n\nCriterios configurados desde el administrador:\n- " . implode("\n- ", $criteria);
    }

    /** @param list<array{role:string,content:string}> $history @param list<array<string,mixed>> $rows @param array<string,mixed> $facts */
    private function composeResponse(array $history, array $interpretation, array $rows, array $facts, string $fallback): string
    {
        try {
            $response = $this->structuredRequest(
                'respuesta_catalogo',
                [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'properties' => ['message' => ['type' => 'string']],
                    'required' => ['message'],
                ],
                'Redactá solamente el mensaje que verá el cliente. Respetá los hechos, los resultados y el material editorial provistos: no inventes productos, variantes, precios, stock ni medidas. Usá el material editorial de Aprende o la guía de talles cuando sea relevante. No enumeres productos en el mensaje porque la interfaz los muestra por separado.' . $this->criteriaInstructions('response'),
                [[
                    'role' => 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => json_encode([
                            'ultimo_mensaje' => $history === [] ? '' : (string) (($history[array_key_last($history)]['content'] ?? '')),
                            'interpretacion' => $this->publicInterpretation($interpretation),
                            'hechos' => $facts + $this->editorialContext($history, $interpretation, $rows),
                            'resultados' => array_map(static fn (array $row): array => [
                                'producto' => (string) ($row['producto'] ?? ''),
                                'variante' => (string) ($row['variante'] ?? ''),
                                'stock' => $row['stock'] ?? null,
                                'medidas' => $row['medidas'] ?? null,
                            ], $rows),
                            'respuesta_segura' => $fallback,
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]],
                ]]
            );
            $message = trim((string) ($response['message'] ?? ''));
            return $message === '' ? $fallback : $message;
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /** @param list<array{role:string,content:string}> $history @param array<string,mixed> $interpretation @param list<array<string,mixed>> $rows @return array<string,mixed> */
    private function editorialContext(array $history, array $interpretation, array $rows): array
    {
        $context = [];
        $sizeGuide = $this->relevantSizeGuide($interpretation, $history, $rows);
        if ($sizeGuide !== null) $context['guia_de_talles'] = $sizeGuide;

        $tutorials = array_map(static function (array $tutorial): array {
            $content = (string) $tutorial['content'];
            return [
                'titulo' => (string) $tutorial['title'],
                'contenido' => function_exists('mb_substr') ? mb_substr($content, 0, 3000, 'UTF-8') : substr($content, 0, 3000),
            ];
        }, array_slice($this->tutorials->publicList(), 0, 12));
        if ($tutorials !== []) $context['tutoriales_aprende'] = $tutorials;

        return $context;
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
        foreach (['texto', 'marca', 'categoria', 'material', 'talle', 'color', 'gramaje', 'tamano', 'tipo', 'uso', 'atributos', 'codigo'] as $field) {
            $filterProperties[$field] = ['type' => ['string', 'null']];
        }
        $requestProperties = [
            'product_term' => ['type' => 'string'],
            'talle' => ['type' => ['string', 'null']],
            'color' => ['type' => ['string', 'null']],
            'material' => ['type' => ['string', 'null']],
            'gramaje' => ['type' => ['string', 'null']],
            'tamano' => ['type' => ['string', 'null']],
            'tipo_papel' => ['type' => ['string', 'null']],
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
