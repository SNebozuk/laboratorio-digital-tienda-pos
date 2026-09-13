<?php
declare(strict_types=1);
namespace LaboratorioDigital;

final class CatalogAiChatService
{
    public function __construct(private readonly array $config, private readonly CatalogAiToolService $tools) {}

    /** @return array{connected:bool} */
    public function status(): array
    {
        $key = trim((string) ($this->config['openai_api_key'] ?? ''));
        return ['connected' => $key !== ''];
    }
    /** @param list<array{role:string,content:string}> $history @return array<string,mixed> */
    public function reply(array $history): array
    {
        if (trim((string)($this->config['openai_api_key'] ?? '')) === '') throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Buscador IA.');
        $input = array_map(static function ($message): array {
            $assistant = ($message['role'] ?? '') === 'assistant';
            return ['role'=>$assistant ? 'assistant' : 'user', 'content'=>[['type'=>$assistant ? 'output_text' : 'input_text', 'text'=>(string)($message['content'] ?? '')]]];
        }, $history);
        $instructions = 'Sos el vendedor de Laboratorio Digital. Respondé en español, breve, directo y natural; no uses frases de buscador como "encontré resultados", "la búsqueda devolvió" o "no encontré coincidencias". Reconstruí la intención actual usando toda la conversación: un mensaje breve como "¿y de algodón?" agrega o modifica esa condición sin borrar producto, público, color, talle ni otros datos ya confirmados. Nunca vuelvas a preguntar un dato que ya está en el historial. Interpretá producto, marca, uso, material, talle/medida/capacidad, código o SKU, atributos y variante, tolerando singular/plural, mayúsculas, acentos, abreviaciones, términos incompletos y errores leves. Consultá siempre el catálogo real antes de responder. En buscarProductos enviá cada concepto en su campo; material debe ir en material y no solamente en atributos. Si la combinación completa no devuelve productos, no concluyas que no existe: repetí automáticamente búsquedas progresivamente más amplias quitando una condición secundaria por vez y terminá buscando el concepto nuevo por sí solo. Así distinguís entre que el concepto exista y que falte una combinación concreta. Nunca digas "no tenemos", "no hay" o "no encontré" por el resultado vacío de una sola consulta. Si existen productos que cumplen parte de la intención, explicá exactamente qué condición falta, por ejemplo "De algodón tenemos, pero infantiles no veo disponibles ahora". Si hay coincidencias completas, respondé como vendedor, por ejemplo "Sí, de algodón también tenemos". Para código o SKU usá obtenerVariantes con codigo. Basá productos, materiales, precio, stock y variantes exclusivamente en las herramientas; no inventes nada. Las coincidencias amplias sirven solo para verificar existencia o explicar alternativas: no las presentes como si cumplieran toda la intención. Mostrá pocas opciones relevantes. Si la opción exacta no tiene stock, informalo y usá buscarAlternativas aclarando qué cambia.';
        $response = $this->request(['model'=>'gpt-5.6-terra','store'=>false,'reasoning'=>['effort'=>'low'],'instructions'=>$instructions,'tools'=>$this->schemas(),'input'=>$input]);
        $toolResults = [];
        $interpretation = [];
        for ($turn = 0; $turn < 8; $turn++) {
            $calls = array_values(array_filter($response['output'] ?? [], static fn($i) => is_array($i) && ($i['type'] ?? '') === 'function_call'));
            if (!$calls) break;
            $outputs=[];
            foreach ($calls as $call) {
                $args = json_decode((string)$call['arguments'], true) ?: [];
                $result = $this->call((string)$call['name'], $args);
                foreach ($args as $key => $value) if ($value !== null && $value !== '') $interpretation[$key] = $value;
                $toolResults[] = ['tool'=>(string)$call['name'], 'arguments'=>$args, 'result'=>$result];
                $outputs[]=['type'=>'function_call_output','call_id'=>$call['call_id'],'output'=>json_encode($result, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
            }
            $response=$this->request(['model'=>'gpt-5.6-terra','store'=>false,'reasoning'=>['effort'=>'low'],'instructions'=>$instructions,'tools'=>$this->schemas(),'input'=>array_merge($response['output'],$outputs)]);
        }
        $text=''; foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $content) if (($content['type'] ?? '') === 'output_text') $text .= $content['text'] ?? '';
        return ['message'=>trim($text) ?: 'No pude preparar una respuesta.','raw_tools'=>$toolResults,'display_results'=>$this->displayResults($toolResults),'interpretation'=>$interpretation];
    }
    /** @param list<array{tool:string,arguments:array<string,mixed>,result:mixed}> $toolResults @return list<array<string,mixed>> */
    private function displayResults(array $toolResults): array
    {
        $best = []; $specificity = -1;
        foreach ($toolResults as $call) {
            if ($call['tool'] !== 'buscarProductos' || !is_array($call['result'])) continue;
            $current = count(array_filter($call['arguments'], static fn($value): bool => $value !== null && $value !== '' && $value !== 0));
            if ($current <= $specificity) continue;
            $specificity = $current;
            $best = array_values(array_filter($call['result'], static fn($row): bool => is_array($row) && isset($row['producto_id'], $row['variante_id'])));
        }
        return $best;
    }
    private function call(string $name, array $args): mixed { return match($name) {'buscarProductos'=>$this->tools->buscarProductos($args),'obtenerVariantes'=>trim((string)($args['codigo']??'')) !== '' ? $this->tools->obtenerVariantesPorCodigo((string)$args['codigo']) : $this->tools->obtenerVariantes((int)($args['producto_id']??0)),'consultarStock'=>$this->tools->consultarStock((int)($args['variante_id']??0)),'buscarAlternativas'=>$this->tools->buscarAlternativas($args),default=>['error'=>'Herramienta inválida']}; }
    private function schemas(): array { $properties=['texto'=>['type'=>['string','null']],'marca'=>['type'=>['string','null']],'categoria'=>['type'=>['string','null']],'material'=>['type'=>['string','null']],'talle'=>['type'=>['string','null']],'color'=>['type'=>['string','null']],'tipo'=>['type'=>['string','null']],'uso'=>['type'=>['string','null']],'atributos'=>['type'=>['string','null']],'variante_id'=>['type'=>['integer','null']]]; $filters=['type'=>'object','additionalProperties'=>false,'properties'=>$properties,'required'=>array_keys($properties)]; return [['type'=>'function','name'=>'buscarProductos','description'=>'Consulta nombre, descripción, categoría y variantes del catálogo real con la intención completa. Enviá producto en texto, marca en marca, material en material, público o clase en tipo y las demás condiciones en su campo. Si queda vacío, repetí consultas más amplias para saber cuál condición falta.','parameters'=>$filters,'strict'=>true],['type'=>'function','name'=>'obtenerVariantes','description'=>'Obtiene todas las variantes reales de un producto identificado o de un código/SKU. Para código/SKU usá codigo; para producto conocido usá producto_id.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['producto_id'=>['type'=>['integer','null']],'codigo'=>['type'=>['string','null']]],'required'=>['producto_id','codigo']],'strict'=>true],['type'=>'function','name'=>'consultarStock','description'=>'Consulta el stock real de una variante ya identificada.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['variante_id'=>['type'=>'integer']],'required'=>['variante_id']],'strict'=>true],['type'=>'function','name'=>'buscarAlternativas','description'=>'Busca alternativas reales cuando una variante exacta no tiene stock. No cambies características sin avisar.','parameters'=>$filters,'strict'=>true]]; }
    private function request(array $payload): array { $h=curl_init(rtrim((string)$this->config['openai_base_url'],'/').'/responses'); if(!$h) throw new \RuntimeException('No se pudo conectar con OpenAI.'); curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->config['openai_api_key'],'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>45]); $body=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);$data=json_decode((string)$body,true);if($status<200||$status>=300||!is_array($data))throw new \RuntimeException((string)($data['error']['message']??'OpenAI no respondió.'));return $data; }
}
