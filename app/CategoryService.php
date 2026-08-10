<?php
declare(strict_types=1);

namespace LaboratorioDigital;

use PDO;

final class CategoryService
{
    public function __construct(private readonly PDO $pdo) {}

    /** @return list<array<string, mixed>> */
    public function tree(): array
    {
        $rows = $this->pdo->query(
            'SELECT c.id, c.parent_id, c.name, c.slug, c.sort_order, c.active,
                    COUNT(p.id) AS product_count
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id
             ORDER BY c.parent_id IS NOT NULL, c.sort_order, c.name'
        )->fetchAll();
        $byId = [];
        foreach ($rows as $row) {
            $row['id'] = (int) $row['id'];
            $row['parent_id'] = $row['parent_id'] === null ? null : (int) $row['parent_id'];
            $row['sort_order'] = (int) $row['sort_order'];
            $row['active'] = (bool) $row['active'];
            $row['product_count'] = (int) $row['product_count'];
            $row['children'] = [];
            $byId[$row['id']] = $row;
        }
        $roots = [];
        foreach ($byId as $id => &$row) {
            if ($row['parent_id'] !== null && isset($byId[$row['parent_id']])) {
                $byId[$row['parent_id']]['children'][] = &$row;
            } else {
                $roots[] = &$row;
            }
        }
        unset($row);
        return $roots;
    }

    public function create(array $data): int
    {
        return Database::immediate($this->pdo, function (PDO $pdo) use ($data): int {
            [$name, $parentId, $sortOrder, $active] = $this->validate($pdo, $data);
            $insert = $pdo->prepare('INSERT INTO categories(name, slug, parent_id, sort_order, active) VALUES(:name, :slug, :parent_id, :sort_order, :active)');
            $insert->execute(['name' => $name, 'slug' => $this->slug($name), 'parent_id' => $parentId, 'sort_order' => $sortOrder, 'active' => $active ? 1 : 0]);
            return (int) $pdo->lastInsertId();
        });
    }

    public function update(int $id, array $data): void
    {
        Database::immediate($this->pdo, function (PDO $pdo) use ($id, $data): void {
            if ($id < 1 || !$this->exists($pdo, $id)) throw new ValidationException('La categoría no existe.');
            [$name, $parentId, $sortOrder, $active] = $this->validate($pdo, $data, $id);
            if ($parentId === $id || ($parentId !== null && $this->isDescendant($pdo, $parentId, $id))) {
                throw new ValidationException('Una categoría no puede depender de sí misma ni de una subcategoría.');
            }
            $update = $pdo->prepare('UPDATE categories SET name=:name, slug=:slug, parent_id=:parent_id, sort_order=:sort_order, active=:active, updated_at=CURRENT_TIMESTAMP WHERE id=:id');
            $update->execute(['id' => $id, 'name' => $name, 'slug' => $this->slug($name), 'parent_id' => $parentId, 'sort_order' => $sortOrder, 'active' => $active ? 1 : 0]);
        });
    }

    public function delete(int $id): void
    {
        Database::immediate($this->pdo, function (PDO $pdo) use ($id): void {
            $delete = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $delete->execute(['id' => $id]);
            if ($delete->rowCount() !== 1) throw new ValidationException('La categoría no existe.');
        });
    }

    /** @return array{0:string,1:?int,2:int,3:bool} */
    private function validate(PDO $pdo, array $data, ?int $selfId = null): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $parentId = filter_var($data['parent_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $sortOrder = filter_var($data['sort_order'] ?? 0, FILTER_VALIDATE_INT);
        if ($name === '') throw new ValidationException('Ingresá un nombre para la categoría.');
        if ($parentId !== false && !$this->exists($pdo, (int) $parentId)) throw new ValidationException('La categoría superior no existe.');
        return [$name, $parentId === false ? null : (int) $parentId, $sortOrder === false ? 0 : (int) $sortOrder, !isset($data['active']) || (bool) $data['active']];
    }

    private function exists(PDO $pdo, int $id): bool { $q=$pdo->prepare('SELECT 1 FROM categories WHERE id=:id'); $q->execute(['id'=>$id]); return $q->fetchColumn() !== false; }
    private function isDescendant(PDO $pdo, int $candidate, int $ancestor): bool { while ($candidate > 0) { if ($candidate === $ancestor) return true; $q=$pdo->prepare('SELECT parent_id FROM categories WHERE id=:id'); $q->execute(['id'=>$candidate]); $parent=$q->fetchColumn(); if ($parent === false || $parent === null) return false; $candidate=(int)$parent; } return false; }
    private function slug(string $value): string { $value=trim((string)preg_replace('~[^a-z0-9]+~','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value) ?: $value))); return trim($value,'-') ?: 'categoria'; }
}
