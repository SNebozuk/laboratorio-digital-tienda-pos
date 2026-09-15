<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CatalogAiConversationService
{
    public function __construct(private readonly PDO $pdo, private readonly CatalogAiChatService $chat, private readonly SettingsService $settings)
    {
    }

    /** @return list<array{role:string,content:string}> */
    public function history(string $token): array
    {
        $query = $this->pdo->prepare('SELECT role, content FROM ai_chat_messages WHERE conversation_token = :token ORDER BY id ASC');
        $query->execute(['token' => $token]);
        return array_map(static fn (array $row): array => ['role' => $row['role'], 'content' => $row['content']], $query->fetchAll());
    }

    /** @return array<string,mixed> */
    public function reply(string $token, string $message): array
    {
        $this->pdo->prepare('INSERT OR IGNORE INTO ai_chat_conversations(token, updated_at) VALUES(:token, CURRENT_TIMESTAMP)')->execute(['token' => $token]);
        $this->add($token, 'user', $message, null);
        $reply = $this->chat->reply(array_slice($this->history($token), -8));
        $this->add($token, 'assistant', (string) $reply['message'], $reply['interpretation'] ?? null);
        $this->pdo->prepare('UPDATE ai_chat_conversations SET updated_at = CURRENT_TIMESTAMP WHERE token = :token')->execute(['token' => $token]);
        $reply['human_help'] = ($reply['display_results'] ?? []) === [] && ($reply['raw_tools'] ?? []) !== [] && empty($reply['needs_clarification']) ? $this->humanHelp() : null;
        return $reply;
    }

    /** @return list<array<string,mixed>> */
    public function recent(): array
    {
        return $this->pdo->query("SELECT c.token, c.updated_at, (SELECT content FROM ai_chat_messages m WHERE m.conversation_token = c.token AND m.role = 'user' ORDER BY m.id DESC LIMIT 1) AS last_message, (SELECT interpretation_json FROM ai_chat_messages m WHERE m.conversation_token = c.token AND m.interpretation_json <> '' ORDER BY m.id DESC LIMIT 1) AS interpretation_json FROM ai_chat_conversations c ORDER BY c.updated_at DESC LIMIT 100")->fetchAll();
    }

    private function add(string $token, string $role, string $content, mixed $interpretation): void
    {
        $this->pdo->prepare('INSERT INTO ai_chat_messages(conversation_token, role, content, interpretation_json) VALUES(:token, :role, :content, :interpretation)')->execute([
            'token' => $token, 'role' => $role, 'content' => $content,
            'interpretation' => is_array($interpretation) ? json_encode($interpretation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
        ]);
    }

    /** @return array<string,string|bool> */
    private function humanHelp(): array
    {
        $hours = (string) ($this->settings->values()['business_hours'] ?? '');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/Argentina/Buenos_Aires'));
        $holiday = $this->argentineHoliday($now);
        if ($holiday !== null) {
            $next = $this->range($hours, 'lunes.*viernes')[0] ?? '9:30';
            return ['available' => false, 'message' => 'Hoy puede ser feriado nacional (' . $holiday . '), por lo que Allessandra podría no estar atendiendo. Cuando retome la atención, podrá responder desde las ' . $next . '.', 'hours' => $hours];
        }
        $day = (int) $now->format('N');
        $range = $day <= 5 ? $this->range($hours, 'lunes.*viernes') : ($day === 6 ? $this->range($hours, 's.bados?') : null);
        if ($range !== null && $now->format('H:i') >= $range[0] && $now->format('H:i') < $range[1]) return ['available' => true, 'message' => 'Allessandra está atendiendo ahora.'];
        $next = $range[0] ?? ($this->range($hours, 'lunes.*viernes')[0] ?? '9:30');
        return ['available' => false, 'message' => 'Allessandra no está atendiendo en este momento. Te podrá responder a partir de las ' . $next . '.', 'hours' => $hours];
    }

    /** @return array{0:string,1:string}|null */
    private function range(string $hours, string $days): ?array
    {
        if (!preg_match('/' . $days . '.*?(\d{1,2}(?::\d{2})?)\s*(?:a|hasta|-)\s*(\d{1,2}(?::\d{2})?)/iu', $hours, $match)) return null;
        $normalise = static function (string $time): string {
            [$hour, $minute] = array_pad(explode(':', $time, 2), 2, '0');
            return sprintf('%02d:%02d', (int) $hour, (int) $minute);
        };
        return [$normalise($match[1]), $normalise($match[2])];
    }

    private function argentineHoliday(\DateTimeImmutable $date): ?string
    {
        $fixed = [
            '01-01' => 'Año Nuevo', '03-24' => 'Día de la Memoria', '04-02' => 'Día del Veterano y de los Caídos en Malvinas',
            '05-01' => 'Día del Trabajador', '05-25' => 'Revolución de Mayo', '06-20' => 'Día de la Bandera',
            '07-09' => 'Día de la Independencia', '12-08' => 'Inmaculada Concepción', '12-25' => 'Navidad',
        ];
        $key = $date->format('m-d');
        if (isset($fixed[$key])) return $fixed[$key];
        $transferable = ['06-17' => 'Paso a la Inmortalidad de Martín Miguel de Güemes', '08-17' => 'Paso a la Inmortalidad de José de San Martín', '10-12' => 'Día del Respeto a la Diversidad Cultural', '11-20' => 'Día de la Soberanía Nacional'];
        foreach ($transferable as $day => $name) {
            [$month, $dayOfMonth] = array_map('intval', explode('-', $day));
            $observed = $date->setDate((int) $date->format('Y'), $month, $dayOfMonth);
            $weekday = (int) $observed->format('N');
            if ($weekday === 2) $observed = $observed->modify('-1 day');
            if ($weekday === 3) $observed = $observed->modify('-2 days');
            if ($weekday === 4) $observed = $observed->modify('+4 days');
            if ($weekday === 5) $observed = $observed->modify('+3 days');
            if ($date->format('Y-m-d') === $observed->format('Y-m-d')) return $name;
        }
        $easter = $this->easterSunday((int) $date->format('Y'));
        $movable = [
            $easter->modify('-48 days')->format('Y-m-d') => 'Carnaval',
            $easter->modify('-47 days')->format('Y-m-d') => 'Carnaval',
            $easter->modify('-2 days')->format('Y-m-d') => 'Viernes Santo',
        ];
        return $movable[$date->format('Y-m-d')] ?? null;
    }

    private function easterSunday(int $year): \DateTimeImmutable
    {
        $a = $year % 19; $b = intdiv($year, 100); $c = $year % 100; $d = intdiv($b, 4); $e = $b % 4;
        $f = intdiv($b + 8, 25); $g = intdiv($b - $f + 1, 3); $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4); $k = $c % 4; $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31); $day = ($h + $l - 7 * $m + 114) % 31 + 1;
        return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), new \DateTimeZone('America/Argentina/Buenos_Aires'));
    }
}
