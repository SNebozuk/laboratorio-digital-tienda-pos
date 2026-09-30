<?php
declare(strict_types=1);

/** Dedicated storage and Cloud API client. No credentials are exposed to the browser. */
final class WhatsAppWorkspace
{
    public function __construct(private PDO $db, private array $config = [])
    {
        $db->exec('CREATE TABLE IF NOT EXISTS wa_workspace_drafts (id INTEGER PRIMARY KEY AUTOINCREMENT, definition TEXT NOT NULL, meta_id TEXT, status TEXT NOT NULL DEFAULT "DRAFT", updated_at TEXT NOT NULL)');
        $db->exec('CREATE TABLE IF NOT EXISTS wa_workspace_messages (id INTEGER PRIMARY KEY AUTOINCREMENT, meta_id TEXT UNIQUE NOT NULL, phone TEXT NOT NULL, contact_name TEXT NOT NULL DEFAULT "", direction TEXT NOT NULL, type TEXT NOT NULL, content TEXT NOT NULL, status TEXT NOT NULL, occurred_at INTEGER NOT NULL, received_at INTEGER NOT NULL DEFAULT 0, status_at INTEGER NOT NULL DEFAULT 0)');
        $db->exec('CREATE INDEX IF NOT EXISTS wa_workspace_messages_phone ON wa_workspace_messages(phone,occurred_at)');
        $db->exec('CREATE TABLE IF NOT EXISTS wa_workspace_events (id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, detail TEXT NOT NULL, created_at TEXT NOT NULL)');
        $db->exec('CREATE TABLE IF NOT EXISTS wa_workspace_receipts (meta_id TEXT NOT NULL,status TEXT NOT NULL,occurred_at INTEGER NOT NULL,PRIMARY KEY(meta_id,status,occurred_at))');
    }

    public function configured(): bool
    {
        return !empty($this->config['enabled']) && !empty($this->config['token']) && preg_match('/^\d+$/', (string)($this->config['waba_id'] ?? '')) && preg_match('/^\d+$/', (string)($this->config['phone_number_id'] ?? ''));
    }

    public function state(): array
    {
        $drafts = $this->db->query('SELECT * FROM wa_workspace_drafts ORDER BY updated_at DESC,id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($drafts as &$draft) $draft['definition'] = json_decode($draft['definition'], true);
        unset($draft);
        return ['configured' => $this->configured(), 'phone_number_id' => $this->configured() ? $this->config['phone_number_id'] : null,
            'drafts' => $drafts, 'events' => $this->db->query('SELECT * FROM wa_workspace_events ORDER BY id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC)];
    }

    public static function definition(array $input): array
    {
        $name = trim((string)($input['name'] ?? ''));
        if (!preg_match('/^[a-z][a-z0-9_]{0,511}$/', $name)) throw new InvalidArgumentException('El nombre debe comenzar con una letra minúscula y contener solo letras minúsculas, números y guiones bajos.');
        $category = (string)($input['category'] ?? 'UTILITY');
        if (!in_array($category, ['UTILITY', 'MARKETING'], true)) throw new InvalidArgumentException('Elegí Utilidad o Marketing.');
        $language = (string)($input['language'] ?? 'es_AR');
        if (!in_array($language, ['es_AR', 'es', 'en_US', 'pt_BR'], true)) throw new InvalidArgumentException('Idioma inválido.');
        $body = trim((string)($input['body'] ?? ''));
        if ($body === '' || mb_strlen($body) > 1024) throw new InvalidArgumentException('El cuerpo debe tener entre 1 y 1024 caracteres.');
        if (str_contains($body, "\t") || preg_match('/ {5,}/', $body)) throw new InvalidArgumentException('El cuerpo no puede contener tabulaciones ni más de cuatro espacios seguidos.');
        preg_match_all('/\{\{([1-9]\d*)\}\}/', $body, $matches);
        $without = preg_replace('/\{\{[1-9]\d*\}\}/', '', $body);
        if (str_contains($without, '{{') || str_contains($without, '}}')) throw new InvalidArgumentException('Usá variables numéricas como {{1}}, {{2}}.');
        $numbers = array_values(array_unique(array_map('intval', $matches[1])));
        sort($numbers);
        if ($numbers && $numbers !== range(1, count($numbers))) throw new InvalidArgumentException('Las variables deben comenzar en {{1}} y ser consecutivas.');
        if (preg_match('/^\{\{|\}\}$/', $body)) throw new InvalidArgumentException('Agregá texto antes y después de las variables.');
        $examples = array_values(array_map(static fn($v) => trim((string)$v), (array)($input['examples'] ?? [])));
        if (count($examples) !== count($numbers) || in_array('', $examples, true)) throw new InvalidArgumentException('Completá un ejemplo para cada variable.');
        foreach ($examples as $example) if (mb_strlen($example) > 256 || str_contains($example, '{{')) throw new InvalidArgumentException('Los ejemplos deben contener valores concretos de hasta 256 caracteres.');
        $components = [];
        $header = trim((string)($input['header'] ?? ''));
        $footer = trim((string)($input['footer'] ?? ''));
        foreach ([$header, $footer] as $text) if (mb_strlen($text) > 60 || preg_match('/[\r\n\t]|\{\{/', $text)) throw new InvalidArgumentException('Encabezado y pie: texto fijo de hasta 60 caracteres.');
        if ($header !== '') $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $header];
        $component = ['type' => 'BODY', 'text' => $body];
        if ($examples) $component['example'] = ['body_text' => [$examples]];
        $components[] = $component;
        if ($footer !== '') $components[] = ['type' => 'FOOTER', 'text' => $footer];
        $buttons = [];
        foreach ((array)($input['buttons'] ?? []) as $button) {
            $text = trim((string)$button);
            if ($text !== '') {
                if (mb_strlen($text) > 25 || preg_match('/[\r\n\t]|\{\{/', $text)) throw new InvalidArgumentException('Los botones deben tener texto fijo de hasta 25 caracteres.');
                $buttons[] = ['type' => 'QUICK_REPLY', 'text' => $text];
            }
        }
        if (count($buttons) > 3 || count(array_unique(array_column($buttons, 'text'))) !== count($buttons)) throw new InvalidArgumentException('Usá hasta tres botones con textos diferentes.');
        if ($buttons) $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
        return ['name' => $name, 'language' => $language, 'category' => $category, 'components' => $components];
    }

    public function save(array $input): array
    {
        self::definition($input);
        $id = (int)($input['id'] ?? 0);
        $json = json_encode(array_intersect_key($input, array_flip(['name','category','language','header','body','footer','examples','buttons'])), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if ($id > 0) {
            $q = $this->db->prepare('UPDATE wa_workspace_drafts SET definition=?,updated_at=? WHERE id=? AND meta_id IS NULL AND status="DRAFT"');
            $q->execute([$json, gmdate('c'), $id]);
            if ($q->rowCount() !== 1) throw new InvalidArgumentException('El borrador no existe o ya fue enviado. Creá una copia.');
        } else {
            $q = $this->db->prepare('INSERT INTO wa_workspace_drafts(definition,updated_at) VALUES(?,?)');
            $q->execute([$json, gmdate('c')]);
            $id = (int)$this->db->lastInsertId();
        }
        return ['id' => $id, 'message' => 'Borrador guardado. No se envió a Meta.'];
    }

    public function templates(?string $after = null): array
    {
        $query = ['fields' => 'id,name,language,status,category,components,rejected_reason', 'limit' => 100];
        if ($after !== null && $after !== '') {
            if (strlen($after) > 2048) throw new InvalidArgumentException('Cursor inválido.');
            $query['after'] = $after;
        }
        $result = $this->request('GET', $this->config['waba_id'] . '/message_templates', $query);
        foreach ($result['data'] ?? [] as $template) {
            $q = $this->db->prepare('UPDATE wa_workspace_drafts SET status=? WHERE meta_id=?');
            $q->execute([(string)($template['status'] ?? 'UNKNOWN'), (string)($template['id'] ?? '')]);
        }
        return ['templates' => $result['data'] ?? [], 'after' => isset($result['paging']['next']) ? ($result['paging']['cursors']['after'] ?? null) : null];
    }

    public function submit(int $id): array
    {
        if (!$this->configured()) throw new RuntimeException('Sin número conectado. No se puede enviar a Meta.');
        $q = $this->db->prepare('SELECT * FROM wa_workspace_drafts WHERE id=?');
        $q->execute([$id]);
        $draft = $q->fetch(PDO::FETCH_ASSOC);
        if (!$draft || $draft['meta_id'] !== null) throw new InvalidArgumentException('Elegí un borrador que todavía no haya sido enviado.');
        // Reserve before making an external mutation: never repeat an uncertain submission automatically.
        $q = $this->db->prepare('UPDATE wa_workspace_drafts SET status="SUBMITTING" WHERE id=? AND status="DRAFT" AND meta_id IS NULL');
        $q->execute([$id]);
        if ($q->rowCount() !== 1) throw new InvalidArgumentException('La solicitud ya está en curso o requiere revisar su resultado en Meta.');
        try {
            $result = $this->request('POST', $this->config['waba_id'] . '/message_templates', self::definition(json_decode($draft['definition'], true)));
            $metaId = (string)($result['id'] ?? '');
            if ($metaId === '') throw new RuntimeException('Meta no devolvió un identificador. Revisá las plantillas antes de volver a intentar.');
            $status = (string)($result['status'] ?? 'PENDING');
            $q = $this->db->prepare('UPDATE wa_workspace_drafts SET meta_id=?,status=?,updated_at=? WHERE id=?');
            $q->execute([$metaId, $status, gmdate('c'), $id]);
            $this->event('template', 'Plantilla ' . json_decode($draft['definition'], true)['name'] . ': ' . $status . ' · ' . $metaId);
            return ['message' => 'Meta recibió la plantilla. Estado: ' . $status, 'meta_id' => $metaId, 'status' => $status];
        } catch (Throwable $e) {
            $this->db->prepare('UPDATE wa_workspace_drafts SET status="CHECK_META" WHERE id=?')->execute([$id]);
            $this->event('template_error', 'No se confirmó la creación. Consultá Meta antes de repetir.');
            throw $e;
        }
    }

    public function delete(int $id): array
    {
        $q = $this->db->prepare('DELETE FROM wa_workspace_drafts WHERE id=? AND meta_id IS NULL AND status="DRAFT"');
        $q->execute([$id]);
        if (!$q->rowCount()) throw new InvalidArgumentException('Solo se pueden eliminar borradores sin enviar.');
        return ['message' => 'Borrador eliminado.'];
    }

    public function request(string $method, string $path, array $data = [], ?array $upload = null): array
    {
        if (!$this->configured()) throw new RuntimeException('Sin número conectado. Podés guardar borradores; los envíos a Meta están deshabilitados.');
        if (!function_exists('curl_init')) throw new RuntimeException('El servidor necesita cURL para conectar con Meta.');
        $url = 'https://graph.facebook.com/v25.0/' . $path;
        if ($method === 'GET' && $data) $url .= '?' . http_build_query($data);
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer ' . $this->config['token'], 'Accept: application/json'];
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 30, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS];
        if ($method !== 'GET') {
            $options[CURLOPT_CUSTOMREQUEST] = $method;
            if ($upload !== null) $options[CURLOPT_POSTFIELDS] = $upload;
            else {
                $headers[] = 'Content-Type: application/json';
                $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            }
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $failed = curl_errno($ch) !== 0;
        curl_close($ch);
        if ($failed) throw new RuntimeException('No se pudo confirmar la respuesta de Meta. Actualizá el estado antes de repetir.');
        $result = json_decode((string)$raw, true);
        if ($code < 200 || $code >= 300 || !is_array($result)) {
            $error = (string)($result['error']['error_user_msg'] ?? $result['error']['message'] ?? 'Meta no aceptó la solicitud.');
            $error = str_replace((string)$this->config['token'], '[privado]', $error);
            throw new RuntimeException(mb_substr($error, 0, 1200));
        }
        return $result;
    }

    public function inbox(?string $phone = null): array
    {
        $contacts = $this->db->query('SELECT phone,MAX(contact_name) AS contact_name,MAX(occurred_at) AS last_at,MAX(received_at) AS last_received FROM wa_workspace_messages GROUP BY phone ORDER BY last_at DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);
        $messages = [];
        if ($phone) {
            $q = $this->db->prepare('SELECT * FROM (SELECT * FROM wa_workspace_messages WHERE phone=? ORDER BY occurred_at DESC,id DESC LIMIT 200) ORDER BY occurred_at,id');
            $q->execute([$phone]);
            $messages = $q->fetchAll(PDO::FETCH_ASSOC);
            foreach ($messages as &$message) $message['content'] = json_decode($message['content'], true);
            unset($message);
        }
        return ['contacts' => $contacts, 'messages' => $messages];
    }

    public function send(array $input, ?array $file = null): array
    {
        if (!$this->configured()) throw new RuntimeException('Conectá el número nuevo antes de enviar.');
        $phone = preg_replace('/\D/', '', (string)($input['phone'] ?? ''));
        if (!preg_match('/^\d{7,15}$/', $phone)) throw new InvalidArgumentException('Ingresá un número con código de país.');
        if (empty($input['consent'])) throw new InvalidArgumentException('Confirmá que el destinatario autorizó recibir mensajes.');
        $type = (string)($input['type'] ?? 'text');
        $content = [];
        $payload = ['messaging_product' => 'whatsapp', 'to' => $phone];
        if ($type !== 'template') {
            $q = $this->db->prepare('SELECT MAX(received_at) FROM wa_workspace_messages WHERE phone=?');
            $q->execute([$phone]);
            if ((int)$q->fetchColumn() < time() - 86400) throw new InvalidArgumentException('La ventana de respuesta está cerrada. Usá una plantilla aprobada.');
        }
        if ($type === 'text') {
            $text = trim((string)($input['text'] ?? ''));
            if ($text === '' || mb_strlen($text) > 4096) throw new InvalidArgumentException('Escribí un mensaje de hasta 4096 caracteres.');
            $content = ['body' => $text];
        } elseif ($type === 'template') {
            $name = (string)($input['template_name'] ?? '');
            $language = (string)($input['language'] ?? 'es_AR');
            if (!preg_match('/^[a-z0-9_]{1,512}$/', $name) || !preg_match('/^[a-z]{2}(?:_[A-Z]{2})?$/', $language)) throw new InvalidArgumentException('Plantilla o idioma inválido.');
            $found = $this->request('GET', $this->config['waba_id'] . '/message_templates', ['name' => $name, 'fields' => 'name,language,status,components']);
            $approved = null;
            foreach ($found['data'] ?? [] as $candidate) if ($candidate['name'] === $name && $candidate['language'] === $language && $candidate['status'] === 'APPROVED') $approved = $candidate;
            if (!$approved) throw new InvalidArgumentException('Meta todavía no aprobó esta plantilla en el idioma elegido.');
            foreach ($approved['components'] ?? [] as $component) {
                if ($component['type'] === 'HEADER' && (($component['format'] ?? 'TEXT') !== 'TEXT' || str_contains($component['text'] ?? '', '{{'))) throw new InvalidArgumentException('Este envío admite plantillas con encabezado de texto fijo.');
                if ($component['type'] === 'BUTTONS') foreach ($component['buttons'] ?? [] as $button) if (str_contains($button['url'] ?? '', '{{') || ($button['type'] ?? '') === 'OTP') throw new InvalidArgumentException('Esta plantilla necesita parámetros especiales en sus botones.');
            }
            $content = ['name' => $name, 'language' => ['code' => $language]];
            $parameters = array_map(static fn($value) => ['type' => 'text', 'text' => (string)$value], (array)($input['parameters'] ?? []));
            foreach ($parameters as $parameter) if ($parameter['text']==='' || mb_strlen($parameter['text'])>1024 || preg_match('/[\r\n\t]/',$parameter['text'])) throw new InvalidArgumentException('Completá los valores de la plantilla con texto de hasta 1024 caracteres, sin saltos de línea.');
            if ($parameters) $content['components'] = [['type' => 'body', 'parameters' => $parameters]];
        } elseif ($type === 'media' && $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) || $file['size'] > 16 * 1024 * 1024) throw new InvalidArgumentException('Adjuntá un archivo válido de hasta 16 MB.');
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $types = ['image/jpeg'=>'image','image/png'=>'image','application/pdf'=>'document','audio/mpeg'=>'audio','audio/ogg'=>'audio','video/mp4'=>'video'];
            if (!isset($types[$mime])) throw new InvalidArgumentException('Admite JPG, PNG, PDF, MP3, OGG y MP4.');
            if ($types[$mime] === 'image' && $file['size'] > 5 * 1024 * 1024) throw new InvalidArgumentException('Las imágenes admiten hasta 5 MB.');
            $media = $this->request('POST', $this->config['phone_number_id'] . '/media', [], ['messaging_product'=>'whatsapp','file'=>new CURLFile($file['tmp_name'], $mime, basename($file['name']))]);
            $type = $types[$mime];
            $content = ['id' => (string)$media['id']];
            if ($type === 'document') $content['filename'] = basename($file['name']);
        } else throw new InvalidArgumentException('Tipo de mensaje inválido.');
        $payload['type'] = $type;
        $payload[$type] = $content;
        $result = $this->request('POST', $this->config['phone_number_id'] . '/messages', $payload);
        $id = (string)($result['messages'][0]['id'] ?? '');
        if ($id === '') throw new RuntimeException('Meta no confirmó el identificador del mensaje.');
        $this->db->prepare('INSERT OR IGNORE INTO wa_workspace_messages(meta_id,phone,direction,type,content,status,occurred_at) VALUES(?,?,?,?,?,?,?)')->execute([$id,$phone,'sent',$type,json_encode($content,JSON_UNESCAPED_UNICODE),'accepted',time()]);
        $q=$this->db->prepare('SELECT status,occurred_at FROM wa_workspace_receipts WHERE meta_id=? ORDER BY occurred_at');$q->execute([$id]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $receipt) $this->applyReceipt($id,$receipt['status'],(int)$receipt['occurred_at']);
        $this->event('message', 'Meta aceptó el mensaje ' . $id);
        return ['message' => 'Meta aceptó el envío. La entrega se confirma por webhook.'];
    }

    public function ingest(array $payload): void
    {
        $this->db->beginTransaction();
        try {
            foreach ($payload['entry'] ?? [] as $entry) foreach ($entry['changes'] ?? [] as $change) {
                $v = $change['value'] ?? [];
                if ((string)($v['metadata']['phone_number_id'] ?? '') !== (string)($this->config['phone_number_id'] ?? '')) continue;
                $names = [];
                foreach ($v['contacts'] ?? [] as $contact) $names[$contact['wa_id']] = $contact['profile']['name'] ?? '';
                foreach ($v['messages'] ?? [] as $m) {
                    if (empty($m['id']) || empty($m['from'])) continue;
                    $type = (string)($m['type'] ?? 'unknown');
                    $at = (int)($m['timestamp'] ?? time());
                    $this->db->prepare('INSERT OR IGNORE INTO wa_workspace_messages(meta_id,phone,contact_name,direction,type,content,status,occurred_at,received_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$m['id'],$m['from'],$names[$m['from']] ?? '','received',$type,json_encode($m[$type] ?? [],JSON_UNESCAPED_UNICODE),'received',$at,$at]);
                }
                foreach ($v['statuses'] ?? [] as $status) {
                    $at = (int)($status['timestamp'] ?? time());
                    $state = (string)($status['status'] ?? 'unknown');
                    $id=(string)($status['id']??'');
                    if ($id==='' || !in_array($state,['sent','delivered','read','failed'],true)) continue;
                    $receipt=$this->db->prepare('INSERT OR IGNORE INTO wa_workspace_receipts(meta_id,status,occurred_at) VALUES(?,?,?)');$receipt->execute([$id,$state,$at]);
                    $this->applyReceipt($id,$state,$at);
                    if ($receipt->rowCount()) $this->event('status', $state . ' · ' . $id . (!empty($status['errors'][0]['title']) ? ' · ' . $status['errors'][0]['title'] : ''));
                }
            }
            $this->db->commit();
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }

    public function event(string $kind, string $detail): void
    {
        $this->db->prepare('INSERT INTO wa_workspace_events(kind,detail,created_at) VALUES(?,?,?)')->execute([$kind,mb_substr($detail,0,1200),gmdate('c')]);
    }

    private function applyReceipt(string $id,string $state,int $at):void
    {
        // Persist even when a webhook beats the send response; never downgrade read/delivered.
        $q=$this->db->prepare('UPDATE wa_workspace_messages SET status=?,status_at=? WHERE meta_id=? AND direction="sent" AND status_at<=? AND NOT (status="read" AND ? IN ("sent","delivered")) AND NOT (status="delivered" AND ?="sent")');
        $q->execute([$state,$at,$id,$at,$state,$state]);
    }

    public function media(string $id): array
    {
        if (!preg_match('/^\d{1,40}$/',$id)) throw new InvalidArgumentException('Archivo inválido.');
        $q=$this->db->prepare('SELECT content FROM wa_workspace_messages WHERE type IN ("image","document","audio","video","sticker")');
        $q->execute(); $known=false;
        while ($row=$q->fetch(PDO::FETCH_ASSOC)) if ((string)(json_decode($row['content'],true)['id'] ?? '') === $id) {$known=true;break;}
        if (!$known) throw new InvalidArgumentException('Este archivo no pertenece a una conversación.');
        $metadata=$this->request('GET',$id);
        $url=(string)($metadata['url']??'');$parts=parse_url($url);$host=strtolower($parts['host']??'');
        if (($parts['scheme']??'')!=='https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || !preg_match('/(?:^|\.)(?:facebook\.com|fbcdn\.net|fbsbx\.com)$/',$host)) throw new RuntimeException('Meta devolvió una URL de archivo inválida.');
        $mime=(string)($metadata['mime_type']??'application/octet-stream');
        if (!in_array($mime,['image/jpeg','image/png','image/webp','application/pdf','audio/mpeg','audio/ogg','audio/ogg; codecs=opus','video/mp4'],true)) $mime='application/octet-stream';
        $buffer='';$ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$this->config['token']],CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>30,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$buffer){if(strlen($buffer)+strlen($chunk)>16*1024*1024)return 0;$buffer.=$chunk;return strlen($chunk);}]);
        $ok=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        if ($ok===false||$code!==200)throw new RuntimeException('No se pudo recuperar el archivo. Puede haber expirado en Meta.');
        return ['bytes'=>$buffer,'mime'=>$mime];
    }
}
