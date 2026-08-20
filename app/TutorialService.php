<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class TutorialService
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function publicList(): array
    {
        return $this->rows(true);
    }

    /** @return list<array<string, mixed>> */
    public function adminList(): array
    {
        return $this->rows(false);
    }

    public function create(array $data): int
    {
        $tutorial = $this->validate($data);
        $statement = $this->pdo->prepare(
            'INSERT INTO tutorials(title, content, image_path, active, sort_order)
             VALUES(:title, :content, :image_path, :active, :sort_order)'
        );
        $statement->execute($tutorial);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $tutorial = $this->validate($data);
        $tutorial['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE tutorials SET title = :title, content = :content,
                image_path = :image_path, active = :active, sort_order = :sort_order,
                updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $statement->execute($tutorial);
        if ($statement->rowCount() !== 1) {
            throw new ValidationException('El tutorial no existe.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function rows(bool $onlyActive): array
    {
        $where = $onlyActive ? 'WHERE active = 1' : '';
        $rows = $this->pdo->query(
            "SELECT id, title, content, image_path, active, sort_order
             FROM tutorials {$where} ORDER BY sort_order, title"
        )->fetchAll();
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'content' => (string) $row['content'],
            'image_path' => $row['image_path'] ?: null,
            'active' => (bool) $row['active'],
            'sort_order' => (int) $row['sort_order'],
        ], $rows);
    }

    /** @return array{title:string,content:string,image_path:?string,active:int,sort_order:int} */
    private function validate(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $image = trim((string) ($data['image_path'] ?? ''));
        if ($title === '') throw new ValidationException('Ingresá el título del tutorial.');
        $titleLength = function_exists('mb_strlen') ? mb_strlen($title, 'UTF-8') : strlen($title);
        if ($titleLength > 180) throw new ValidationException('El título es demasiado largo.');
        if ($content === '') throw new ValidationException('Ingresá el contenido del tutorial.');
        if ($image !== '' && (!str_starts_with($image, '/') || strlen($image) > 500)) {
            throw new ValidationException('La imagen no es válida.');
        }
        return [
            'title' => $title,
            'content' => $content,
            'image_path' => $image === '' ? null : $image,
            'active' => !isset($data['active']) || filter_var($data['active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];
    }
}
