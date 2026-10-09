<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class DashboardRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function metrics(): array
    {
        $statement = $this->db->query(
            'SELECT COUNT(*) AS reader_count,
             SUM(CASE WHEN expires_on IS NOT NULL AND expires_on < CURRENT_DATE THEN 1 ELSE 0 END) AS expired_card_count
             FROM readers WHERE is_active = 1'
        );
        return $statement->fetch() ?: ['reader_count' => 0, 'expired_card_count' => 0];
    }

    public function categoryStats(): array
    {
        return $this->db->query(
            'SELECT c.id, c.name, COUNT(DISTINCT b.id) AS title_count, COALESCE(SUM((SELECT COUNT(*) FROM book_copies cp WHERE cp.book_id=b.id AND cp.status<>"retired")), 0) AS copy_count
             FROM categories c LEFT JOIN books b ON b.category_id = c.id AND b.is_active = 1
             GROUP BY c.id ORDER BY title_count DESC, c.name'
        )->fetchAll();
    }

    public function reservationCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM reservations WHERE status IN ("pending", "ready")')->fetchColumn();
    }

    public function loanedRecently(int $days = 30): int
    {
        $days = max(1, min(365, $days));
        return (int) $this->db->query(
            'SELECT COUNT(*) FROM loans WHERE borrowed_at >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $days . ' DAY)'
        )->fetchColumn();
    }
}
