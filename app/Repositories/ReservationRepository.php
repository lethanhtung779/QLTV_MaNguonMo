<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class ReservationRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function create(int $readerId, int $bookId): int
    {
        $statement = $this->db->prepare('INSERT INTO reservations (reader_id, book_id) VALUES (:reader_id, :book_id)');
        $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId]);
        return (int) $this->db->lastInsertId();
    }

    public function forReader(int $readerId): array
    {
        $statement = $this->db->prepare(
            'SELECT res.*, b.book_code, b.title, b.author, cp.copy_code
             FROM reservations res JOIN books b ON b.id = res.book_id
             LEFT JOIN book_copies cp ON cp.id = res.book_copy_id
             WHERE res.reader_id = :reader_id ORDER BY res.reserved_at DESC'
        );
        $statement->execute(['reader_id' => $readerId]);
        return $statement->fetchAll();
    }

    public function hasActiveForBook(int $bookId): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM reservations WHERE book_id = :book_id AND status IN ("pending", "ready"))'
        );
        $statement->execute(['book_id' => $bookId]);
        return (bool) $statement->fetchColumn();
    }
}
