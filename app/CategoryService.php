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
            if ($id < 1 || !$this->exists($pdo, $id)) {
                throw new ValidationException('La categoría no existe.');
            }
            $releaseProducts = $pdo->prepare('UPDATE products SET category_id = NULL WHERE category_id = :id');
            $releaseProducts->execute(['id' => $id]);
            $releaseChildren = $pdo->prepare('UPDATE categories SET parent_id = NULL, updated_at = CURRENT_TIMESTAMP WHERE parent_id = :id');
            $releaseChildren->execute(['id' => $id]);
            $delete = $pdo->prepare('DELETE FROM categories WHERE id = :id');
            $delete->execute(['id' => $id]);
            if ($delete->rowCount() !== 1) throw new ValidationException('La categoría no existe.');
        });
    }

    public function move(int $id, int $targetId, string $position): void
    {
        Database::immediate($this->pdo, function (PDO $pdo) use ($id, $targetId, $position): void {
            if ($id < 1 || $targetId < 1 || $id === $targetId) {
                throw new ValidationException('Elegí otra categoría como destino.');
            }
            $query = $pdo->prepare('SELECT id, parent_id FROM categories WHERE id IN (:id, :target_id)');
            $query->execute(['id' => $id, 'target_id' => $targetId]);
            $categories = [];
            foreach ($query->fetchAll() as $row) {
                $categories[(int) $row['id']] = $row['parent_id'] === null ? null : (int) $row['parent_id'];
            }
            if (!array_key_exists($id, $categories) || !array_key_exists($targetId, $categories)) {
                throw new ValidationException('La categoría de origen o destino ya no existe.');
            }

            $oldParent = $categories[$id];
            $newParent = $categories[$targetId];
            if ($newParent !== null && $this->isDescendant($pdo, $newParent, $id)) {
                throw new ValidationException('No se puede mover una categoría dentro de una subcategoría propia.');
            }

            $siblings = static function (PDO $pdo, ?int $parentId): array {
                if ($parentId === null) {
                    return array_map('intval', $pdo->query('SELECT id FROM categories WHERE parent_id IS NULL ORDER BY sort_order, name')->fetchAll(PDO::FETCH_COLUMN));
                }
                $statement = $pdo->prepare('SELECT id FROM categories WHERE parent_id = :parent_id ORDER BY sort_order, name');
                $statement->execute(['parent_id' => $parentId]);
                return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
            };
            $saveOrder = static function (PDO $pdo, array $ids): void {
                $update = $pdo->prepare('UPDATE categories SET sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
                foreach (array_values($ids) as $index => $categoryId) {
                    $update->execute(['id' => $categoryId, 'sort_order' => ($index + 1) * 10]);
                }
            };

            $oldSiblings = array_values(array_filter($siblings($pdo, $oldParent), static fn (int $categoryId): bool => $categoryId !== $id));
            $newSiblings = $oldParent === $newParent
                ? $oldSiblings
                : array_values(array_filter($siblings($pdo, $newParent), static fn (int $categoryId): bool => $categoryId !== $id));
            $targetIndex = array_search($targetId, $newSiblings, true);
            if ($targetIndex === false) {
                throw new ValidationException('No se encontró la posición de destino.');
            }
            array_splice($newSiblings, $targetIndex + ($position === 'after' ? 1 : 0), 0, [$id]);

            $move = $pdo->prepare('UPDATE categories SET parent_id = :parent_id, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $move->execute(['id' => $id, 'parent_id' => $newParent]);
            if ($oldParent !== $newParent) {
                $saveOrder($pdo, $oldSiblings);
            }
            $saveOrder($pdo, $newSiblings);
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
