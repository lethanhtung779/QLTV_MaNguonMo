<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT c.*, COUNT(DISTINCT b.id) AS book_count
             FROM categories c LEFT JOIN books b ON b.category_id = c.id AND b.is_active = 1
             GROUP BY c.id ORDER BY c.name'
        )->fetchAll();
    }

    public function create(string $name, string $description): void
    {
        $statement = $this->db->prepare('INSERT INTO categories (name, description) VALUES (:name, :description)');
        $statement->execute(['name' => $name, 'description' => $description ?: null]);
    }

    public function update(int $id, string $name, string $description): void
    {
        $statement = $this->db->prepare('UPDATE categories SET name = :name, description = :description WHERE id = :id');
        $statement->execute(['id' => $id, 'name' => $name, 'description' => $description ?: null]);
    }

    public function delete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT id FROM categories WHERE id = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            if (!$statement->fetch()) {
                $this->db->rollBack();
                return false;
            }

            $statement = $this->db->prepare('SELECT COUNT(*) FROM books WHERE category_id = :id');
            $statement->execute(['id' => $id]);
            if ((int) $statement->fetchColumn() > 0) {
                $this->db->rollBack();
                return false;
            }

            $statement = $this->db->prepare('DELETE FROM categories WHERE id = :id');
            $statement->execute(['id' => $id]);
            $this->db->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
