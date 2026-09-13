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
        if ($key === '') return ['connected' => false];
        $handle = curl_init(rtrim((string) $this->config['openai_base_url'], '/') . '/models/gpt-5.6-terra');
        if (!$handle) return ['connected' => false];
        curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key], CURLOPT_TIMEOUT => 5]);
        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        return ['connected' => $status >= 200 && $status < 300];
    }
    /** @param list<array{role:string,content:string}> $history @return array<string,mixed> */
    public function reply(array $history): array
    {
        if (trim((string)($this->config['openai_api_key'] ?? '')) === '') throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Buscador IA.');
        $input = array_map(static function ($message): array {
            $assistant = ($message['role'] ?? '') === 'assistant';
            return ['role'=>$assistant ? 'assistant' : 'user', 'content'=>[['type'=>$assistant ? 'output_text' : 'input_text', 'text'=>(string)($message['content'] ?? '')]]];
        }, $history);
        $instructions = 'Sos el asistente de ventas de Laboratorio Digital. Conversá en español de forma breve y natural, conservando todos los datos confirmados en el historial. Antes de mostrar productos, evaluá si entendés con suficiente precisión qué necesita el cliente. No asumas ningún dato importante: producto, categoría, medida, tamaño, material, modelo, uso, compatibilidad, color, cantidad, presentación ni cualquier atributo que diferencie opciones. Si falta un dato que pueda cambiar el producto correcto, hacé exactamente una pregunta breve y útil y no muestres resultados todavía; podés hacer otra pregunta en el turno siguiente. Las herramientas pueden ayudarte a inspeccionar el catálogo para detectar opciones o ambigüedades, pero no presentes coincidencias hasta aclararlas. Cuando la necesidad esté suficientemente definida, usá siempre las herramientas para consultar el catálogo real y basá toda afirmación comercial exclusivamente en sus resultados. Nunca inventes productos, variantes, atributos, precios ni stock. No afirmes que algo no existe sin haberlo buscado. Mostrá pocas coincidencias relevantes y, al mostrarlas, no cierres la respuesta con otra pregunta. Si la opción exacta no tiene stock, informalo, usá buscarAlternativas y avisá claramente qué característica cambia en cada alternativa. No exijas comandos ni códigos.';
        $response = $this->request(['model'=>'gpt-5.6-terra','store'=>false,'reasoning'=>['effort'=>'low'],'instructions'=>$instructions,'tools'=>$this->schemas(),'input'=>$input]);
        $toolResults = [];
        $interpretation = [];
        for ($turn = 0; $turn < 4; $turn++) {
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
        return ['message'=>trim($text) ?: 'No pude preparar una respuesta.','raw_tools'=>$toolResults,'interpretation'=>$interpretation];
    }
    private function call(string $name, array $args): mixed { return match($name) {'buscarProductos'=>$this->tools->buscarProductos($args),'obtenerVariantes'=>$this->tools->obtenerVariantes((int)($args['producto_id']??0)),'consultarStock'=>$this->tools->consultarStock((int)($args['variante_id']??0)),'buscarAlternativas'=>$this->tools->buscarAlternativas($args),default=>['error'=>'Herramienta inválida']}; }
    private function schemas(): array { $properties=['texto'=>['type'=>['string','null']],'categoria'=>['type'=>['string','null']],'talle'=>['type'=>['string','null']],'color'=>['type'=>['string','null']],'tipo'=>['type'=>['string','null']],'uso'=>['type'=>['string','null']],'atributos'=>['type'=>['string','null']],'variante_id'=>['type'=>['integer','null']]]; $filters=['type'=>'object','additionalProperties'=>false,'properties'=>$properties,'required'=>array_keys($properties)]; return [['type'=>'function','name'=>'buscarProductos','description'=>'Inspecciona y busca productos y variantes reales mediante filtros opcionales. Usala para validar coincidencias y ambigüedades del catálogo; no implica que debas mostrar resultados todavía.','parameters'=>$filters,'strict'=>true],['type'=>'function','name'=>'obtenerVariantes','description'=>'Obtiene todas las variantes reales de un producto ya identificado.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['producto_id'=>['type'=>'integer']],'required'=>['producto_id']],'strict'=>true],['type'=>'function','name'=>'consultarStock','description'=>'Consulta el stock real de una variante ya identificada.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['variante_id'=>['type'=>'integer']],'required'=>['variante_id']],'strict'=>true],['type'=>'function','name'=>'buscarAlternativas','description'=>'Busca alternativas reales cuando una variante exacta no tiene stock. No cambies características sin avisar.','parameters'=>$filters,'strict'=>true]]; }
    private function request(array $payload): array { $h=curl_init(rtrim((string)$this->config['openai_base_url'],'/').'/responses'); if(!$h) throw new \RuntimeException('No se pudo conectar con OpenAI.'); curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->config['openai_api_key'],'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>45]); $body=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);$data=json_decode((string)$body,true);if($status<200||$status>=300||!is_array($data))throw new \RuntimeException((string)($data['error']['message']??'OpenAI no respondió.'));return $data; }
}
