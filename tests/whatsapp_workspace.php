<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/WhatsAppWorkspace.php';
function check(bool $condition,string $message):void{if(!$condition)throw new RuntimeException($message);}
function rejects(callable $fn,string $message):void{try{$fn();}catch(InvalidArgumentException|RuntimeException $e){return;}throw new RuntimeException($message);}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$w=new WhatsAppWorkspace($db);
$definition=['name'=>'pedido_listo','category'=>'UTILITY','language'=>'es_AR','body'=>'Hola {{1}}, tu pedido {{2}} está listo.','examples'=>['María','1234'],'header'=>'Tu pedido','footer'=>'Gracias','buttons'=>['Ver pedido','Necesito ayuda']];
$payload=WhatsAppWorkspace::definition($definition);
check($payload['components'][1]['example']['body_text']===[['María','1234']],'Meta examples payload');
check(count($payload['components'][3]['buttons'])===2,'Quick replies');
rejects(fn()=>WhatsAppWorkspace::definition(array_replace($definition,['body'=>'Hola {{2}}, listo.'])),'Reject skipped variable');
rejects(fn()=>WhatsAppWorkspace::definition(array_replace($definition,['examples'=>['María']])),'Require complete examples');
rejects(fn()=>WhatsAppWorkspace::definition(array_replace($definition,['name'=>'Pedido Listo'])),'Reject invalid Meta name');
rejects(fn()=>WhatsAppWorkspace::definition(array_replace($definition,['category'=>'AUTHENTICATION'])),'Reject unsupported auth templates');
rejects(fn()=>WhatsAppWorkspace::definition(array_replace($definition,['buttons'=>['Uno','Dos','Tres','Cuatro']])),'Reject excessive buttons');
$saved=$w->save($definition);check($saved['id']===1,'Save first draft');
check(!$w->state()['configured'],'Disconnected by default');
rejects(fn()=>$w->submit(1),'No Meta submit without config');
check($w->state()['drafts'][0]['status']==='DRAFT','Disconnected submission cannot lock draft');
$w->save($definition+['id'=>1]);check(count($w->state()['drafts'])===1,'Edit instead of insert');
$db->exec('UPDATE wa_workspace_drafts SET status="CHECK_META" WHERE id=1');
rejects(fn()=>$w->save($definition+['id'=>1]),'Cannot edit uncertain submission');
rejects(fn()=>$w->delete(1),'Cannot delete uncertain submission');
$connected=new WhatsAppWorkspace($db,['enabled'=>true,'token'=>'fake-test-not-a-credential','phone_number_id'=>'123','waba_id'=>'456']);
$incoming=['entry'=>[['changes'=>[['value'=>['metadata'=>['phone_number_id'=>'123'],'contacts'=>[['wa_id'=>'5493411234567','profile'=>['name'=>'Prueba']]],'messages'=>[['id'=>'wamid.test','from'=>'5493411234567','timestamp'=>(string)time(),'type'=>'text','text'=>['body'=>'Hola']]]]]]]]];
$connected->ingest($incoming);$connected->ingest($incoming);
check(count($connected->inbox('5493411234567')['messages'])===1,'Webhook deduplication');
check($connected->inbox()['contacts'][0]['contact_name']==='Prueba','Received contact');
$incoming['entry'][0]['changes'][0]['value']['metadata']['phone_number_id']='999';
$incoming['entry'][0]['changes'][0]['value']['messages'][0]['id']='wamid.foreign';$connected->ingest($incoming);
check(count($connected->inbox('5493411234567')['messages'])===1,'Foreign phone excluded');
$db->exec('INSERT INTO wa_workspace_messages(meta_id,phone,direction,type,content,status,occurred_at) VALUES("wamid.outgoing","5493411234567","sent","text","{}","accepted",1)');
$status=function(string $value,int $at)use($connected){$connected->ingest(['entry'=>[['changes'=>[['value'=>['metadata'=>['phone_number_id'=>'123'],'statuses'=>[['id'=>'wamid.outgoing','status'=>$value,'timestamp'=>(string)$at]]]]]]]]);};
$status('read',200);$status('delivered',300);$status('sent',100);
check($db->query('SELECT status FROM wa_workspace_messages WHERE meta_id="wamid.outgoing"')->fetchColumn()==='read','Late webhooks cannot downgrade read');
check($db->query('SELECT status FROM wa_workspace_messages WHERE meta_id="wamid.test"')->fetchColumn()==='received','Delivery status never changes incoming messages');
rejects(fn()=>$w->send(['phone'=>'5493411234567','text'=>'Hola','consent'=>true]),'Disconnected sends rejected');
echo "WhatsApp workspace tests passed: payload validation, local drafts, disconnection, tenant phone filtering, webhook deduplication and delivery order.\n";
