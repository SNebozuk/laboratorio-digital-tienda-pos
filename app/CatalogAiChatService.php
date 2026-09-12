<?php
declare(strict_types=1);
namespace LaboratorioDigital;

final class CatalogAiChatService
{
    public function __construct(private readonly array $config, private readonly CatalogAiToolService $tools) {}
    /** @param list<array{role:string,content:string}> $history @return array<string,mixed> */
    public function reply(array $history): array
    {
        if (trim((string)($this->config['openai_api_key'] ?? '')) === '') throw new \RuntimeException('Configurá OPENAI_API_KEY en el servidor para usar el Buscador IA.');
        $input = array_map(static fn($m) => ['role' => $m['role'] === 'assistant' ? 'assistant' : 'user', 'content' => [['type'=>'input_text','text'=>(string)$m['content']]]], $history);
        $response = $this->request(['model'=>'gpt-5.6-terra','store'=>false,'reasoning'=>['effort'=>'low'],'instructions'=>'Sos vendedor de Laboratorio Digital. Respondé breve en español, una pregunta por vez. Para todo dato comercial usá herramientas; nunca inventes precio, stock o productos. Si faltan tipo, talle o color importantes, preguntá antes de listar. Si no hay stock, usá buscarAlternativas y aclarar cualquier cambio.','tools'=>$this->schemas(),'input'=>$input]);
        for ($turn = 0; $turn < 4; $turn++) {
            $calls = array_values(array_filter($response['output'] ?? [], static fn($i) => is_array($i) && ($i['type'] ?? '') === 'function_call'));
            if (!$calls) break;
            $outputs=[];
            foreach ($calls as $call) $outputs[]=['type'=>'function_call_output','call_id'=>$call['call_id'],'output'=>json_encode($this->call((string)$call['name'], json_decode((string)$call['arguments'],true) ?: []), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
            $response=$this->request(['model'=>'gpt-5.6-terra','store'=>false,'reasoning'=>['effort'=>'low'],'instructions'=>'Respondé solo con información obtenida mediante herramientas.','tools'=>$this->schemas(),'input'=>array_merge($response['output'],$outputs)]);
        }
        $text=''; foreach (($response['output'] ?? []) as $item) foreach (($item['content'] ?? []) as $content) if (($content['type'] ?? '') === 'output_text') $text .= $content['text'] ?? '';
        return ['message'=>trim($text) ?: 'No pude preparar una respuesta.','raw_tools'=>[]];
    }
    private function call(string $name, array $args): mixed { return match($name) {'buscarProductos'=>$this->tools->buscarProductos($args),'obtenerVariantes'=>$this->tools->obtenerVariantes((int)($args['producto_id']??0)),'consultarStock'=>$this->tools->consultarStock((int)($args['variante_id']??0)),'buscarAlternativas'=>$this->tools->buscarAlternativas($args),default=>['error'=>'Herramienta inválida']}; }
    private function schemas(): array { $filters=['type'=>'object','additionalProperties'=>false,'properties'=>['texto'=>['type'=>'string'],'categoria'=>['type'=>'string'],'talle'=>['type'=>'string'],'color'=>['type'=>'string'],'tipo'=>['type'=>'string'],'uso'=>['type'=>'string'],'atributos'=>['type'=>'string'],'variante_id'=>['type'=>'integer']],'required'=>[]]; return [['type'=>'function','name'=>'buscarProductos','description'=>'Busca productos y variantes reales.','parameters'=>$filters,'strict'=>true],['type'=>'function','name'=>'obtenerVariantes','description'=>'Obtiene variantes reales de un producto.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['producto_id'=>['type'=>'integer']],'required'=>['producto_id']],'strict'=>true],['type'=>'function','name'=>'consultarStock','description'=>'Consulta stock real de una variante.','parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['variante_id'=>['type'=>'integer']],'required'=>['variante_id']],'strict'=>true],['type'=>'function','name'=>'buscarAlternativas','description'=>'Busca alternativas reales cuando falta stock.','parameters'=>$filters,'strict'=>true]]; }
    private function request(array $payload): array { $h=curl_init(rtrim((string)$this->config['openai_base_url'],'/').'/responses'); if(!$h) throw new \RuntimeException('No se pudo conectar con OpenAI.'); curl_setopt_array($h,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->config['openai_api_key'],'Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),CURLOPT_TIMEOUT=>45]); $body=curl_exec($h);$status=(int)curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);$data=json_decode((string)$body,true);if($status<200||$status>=300||!is_array($data))throw new \RuntimeException((string)($data['error']['message']??'OpenAI no respondió.'));return $data; }
}
