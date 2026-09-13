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
        $instructions = 'Sos el asistente de ventas de Laboratorio Digital. Conversá en español de forma breve y natural y conservá todos los datos confirmados en el historial: nunca los vuelvas a preguntar. Primero interpretá qué quiso decir el cliente y extraé, cuando estén presentes, producto, marca, uso, talle/medida/capacidad, código o SKU y variante. Entendé singular/plural, mayúsculas, acentos, abreviaciones, palabras incompletas y errores ortográficos leves; no copies literalmente la frase del cliente como búsqueda si podés expresar su intención con términos más útiles. Antes de hacer una pregunta usá buscarProductos para consultar el catálogo real con lo que ya sabés. Para búsquedas por código o SKU usá obtenerVariantes con el código; si no resuelve, buscá también con el producto o marca inferidos. Si la primera consulta no devuelve resultados, ampliá automáticamente la búsqueda: probá por separado producto, marca, uso o términos parciales que inferiste. Solo después de esas alternativas podés decir que no existe. Si aparece una marca con varios productos o categorías, informalo y preguntá cuál de esas opciones busca; no pidas atributos ajenos, como material, antes de consultar esa marca. Cuando producto, material y uso ya estén confirmados en el historial, buscá y mostrale coincidencias reales en vez de volver a preguntar. Usá siempre las herramientas antes de afirmar productos, variantes, precios o stock; nunca inventes datos. Mostrá pocas coincidencias relevantes y, al mostrarlas, no cierres con una pregunta salvo que la búsqueda haya quedado realmente ambigua. Si la opción exacta no tiene stock, informalo, usá buscarAlternativas y aclarale qué cambia. No exijas comandos ni códigos.';
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
    private function call(string $name, array $args): mixed { return match($name) {'buscarProductos'=>$this->tools->buscarProductos($args),'obtenerVariantes'=>trim((string)($args['codigo']??'')) !== '' ? $this->tools->obtenerVariantesPorCodigo((string)$args['codigo']) : $this->tools->obtenerVariantes((int)($args['producto_id']??0)),'consultarStock'=>$this->tools->consultarStock((int)($args['variante_id']??0)),'buscarAlternativas'=>$this->tools->buscarAlternativas($args),default=>['error'=>'Herramienta inválida']}; }
    private function schemas(): array { $properties=['texto'=>['type'=>['string','null']],'marca'=>['type'=>['string','null']],'categoria'=>['type'=>['string','null']],'talle'=>['type'=>['string','null']],'color'=>['type'=>['string','null']],'tipo'=>['type'=>['string','null']],'uso'=>['type'=>['string','null']],'atributos'=>['type'=>['string','null']],'variante_id'=>['type'=>['integer','null']]]; $filters=['type'=>'object','additionalProperties'=>false,'properties'=>$properties,'required'=>array_keys($properties)]; return [['type'=>'function','name'=>'buscarProductos','description'=>'Consulta productos y variantes reales usando la intención interpretada: producto, marca, uso, talle, medida, capacidad, color o términos parciales. Usala antes de preguntar; si no hay coincidencias, probá búsquedas más amplias con cada dato inferido por separado.','parameters'=>$filters,'strict'=>true],['type'=>'function','name'=>'obtenerVariantes','description'=>'Obtiene todas las variantes reales de un producto identificado o de un código/SKU. Para código/SKU usá codigo; para producto conocido usá producto_id.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['producto_id'=>['type'=>['integer','null']],'codigo'=>['type'=>['string','null']]],'required'=>['producto_id','codigo']],'strict'=>true],['type'=>'function','name'=>'consultarStock','description'=>'Consulta el stock real de una variante ya identificada.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['variante_id'=>['type'=>'integer']],'required'=>['variante_id']],'strict'=>true],['type'=>'function','name'=>'buscarAlternativas','description'=>'Busca alternativas reales cuando una variante exacta no tiene stock. No cambies características sin avisar.','parameters'=>$filters,'strict'=>true]]; }
    private function request(array $payload): array { $h=curl_init(rtrim((string)$this->config['openai_base_url'],'/').'/responses'); if(!$h) throw new \RuntimeException('No se pudo conectar con OpenAI.'); curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->config['openai_api_key'],'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>45]); $body=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);$data=json_decode((string)$body,true);if($status<200||$status>=300||!is_array($data))throw new \RuntimeException((string)($data['error']['message']??'OpenAI no respondió.'));return $data; }
}
