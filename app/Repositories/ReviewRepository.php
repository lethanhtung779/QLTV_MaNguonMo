<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class ReviewRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function addOrUpdate(int $bookId, int $readerId, int $rating, ?string $comment): void
    {
        $rating = max(1, min(5, $rating));
        $comment = $comment ? trim(mb_substr($comment, 0, 1000)) : null;

        $stmt = $this->db->prepare('
            INSERT INTO book_reviews (book_id, reader_id, rating, comment, is_approved, created_at, updated_at)
            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), updated_at = NOW()
        ');
        $stmt->execute([$bookId, $readerId, $rating, $comment]);
    }

    public function userReview(int $bookId, int $readerId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM book_reviews WHERE book_id = ? AND reader_id = ?');
        $stmt->execute([$bookId, $readerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function forBook(int $bookId, bool $approvedOnly = true): array
    {
        $sql = '
            SELECT br.*, r.full_name, r.card_number
            FROM book_reviews br
            JOIN readers r ON r.id = br.reader_id
            WHERE br.book_id = ?
        ';
        if ($approvedOnly) {
            $sql .= ' AND br.is_approved = 1';
        }
        $sql .= ' ORDER BY br.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function summaryForBook(int $bookId): array
    {
        $stmt = $this->db->prepare('
            SELECT COUNT(*) AS total_reviews,
                   COALESCE(AVG(rating), 0) AS average_rating,
                   SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS stars_5,
                   SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS stars_4,
                   SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS stars_3,
                   SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS stars_2,
                   SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS stars_1
            FROM book_reviews
            WHERE book_id = ? AND is_approved = 1
        ');
        $stmt->execute([$bookId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'total' => (int) ($res['total_reviews'] ?? 0),
            'average' => round((float) ($res['average_rating'] ?? 0), 1),
            'stars' => [
                5 => (int) ($res['stars_5'] ?? 0),
                4 => (int) ($res['stars_4'] ?? 0),
                3 => (int) ($res['stars_3'] ?? 0),
                2 => (int) ($res['stars_2'] ?? 0),
                1 => (int) ($res['stars_1'] ?? 0),
            ]
        ];
    }

    public function listAll(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->db->prepare('
            SELECT br.*, b.title AS book_title, b.book_code, r.full_name AS reader_name, r.card_number
            FROM book_reviews br
            JOIN books b ON b.id = br.book_id
            JOIN readers r ON r.id = br.reader_id
            ORDER BY br.created_at DESC
            LIMIT ? OFFSET ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function approve(int $reviewId, bool $isApproved): void
    {
        $stmt = $this->db->prepare('UPDATE book_reviews SET is_approved = ? WHERE id = ?');
        $stmt->execute([$isApproved ? 1 : 0, $reviewId]);
    }

    public function delete(int $reviewId, ?int $readerId = null): bool
    {
        if ($readerId !== null) {
            $stmt = $this->db->prepare('DELETE FROM book_reviews WHERE id = ? AND reader_id = ?');
            $stmt->execute([$reviewId, $readerId]);
        } else {
            $stmt = $this->db->prepare('DELETE FROM book_reviews WHERE id = ?');
            $stmt->execute([$reviewId]);
        }
        return $stmt->rowCount() > 0;
    }
}
