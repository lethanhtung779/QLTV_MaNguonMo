<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class FavoriteRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function isFavorite(int $readerId, int $bookId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM book_favorites WHERE reader_id = ? AND book_id = ?');
        $stmt->execute([$readerId, $bookId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function toggle(int $readerId, int $bookId): bool
    {
        if ($this->isFavorite($readerId, $bookId)) {
            $stmt = $this->db->prepare('DELETE FROM book_favorites WHERE reader_id = ? AND book_id = ?');
            $stmt->execute([$readerId, $bookId]);
            return false;
        }

        $stmt = $this->db->prepare('INSERT INTO book_favorites (reader_id, book_id, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$readerId, $bookId]);
        return true;
    }

    public function listForReader(int $readerId): array
    {
        $stmt = $this->db->prepare('
            SELECT b.*, c.name AS category_name, bf.created_at AS favorited_at,
                   (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = "available") AS available_copies
            FROM book_favorites bf
            JOIN books b ON b.id = bf.book_id
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE bf.reader_id = ?
            ORDER BY bf.created_at DESC
        ');
        $stmt->execute([$readerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countForReader(int $readerId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM book_favorites WHERE reader_id = ?');
        $stmt->execute([$readerId]);
        return (int) $stmt->fetchColumn();
    }
}
