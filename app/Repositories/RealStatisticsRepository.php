<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

/**
 * Read-only operational reporting. The old local demo seeder tags its fixture
 * books as DEMO-B..., readers as DEMO-READ..., and loans as DEMO_FIXTURE:...
 * Those records remain in the database for QA, but do not belong in KPI/CSV.
 *
 * This does not certify that every other row represents a real-world loan.
 * Provenance remains a separate operational concern.
 */
final class RealStatisticsRepository
{
    public const BOOK_FILTER = "b.book_code NOT LIKE 'DEMO-%'";
    public const READER_FILTER = "r.card_number NOT LIKE 'DEMO-%'";
    public const LOAN_FILTER = "b.book_code NOT LIKE 'DEMO-%' AND r.card_number NOT LIKE 'DEMO-%' AND COALESCE(l.note, '') NOT LIKE 'DEMO_FIXTURE:%'";

    public function __construct(private readonly PDO $db) {}

    public function bookMetrics(): array
    {
        $s = $this->db->query(
            'SELECT COUNT(DISTINCT b.id) AS title_count, COUNT(cp.id) AS copy_count,
                    COALESCE(SUM(cp.status = "available"), 0) AS available_count,
                    COALESCE(SUM(cp.status = "on_loan"), 0) AS loaned_count
             FROM books b LEFT JOIN book_copies cp ON cp.book_id = b.id
             WHERE '.self::BOOK_FILTER
        );
        return $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function readerMetrics(): array
    {
        $s = $this->db->query(
            'SELECT COUNT(*) AS reader_count,
                    COALESCE(SUM(expires_on IS NOT NULL AND expires_on < CURRENT_DATE),0) AS expired_card_count
             FROM readers r WHERE r.is_active = 1 AND '.self::READER_FILTER
        );
        return $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function loanMetrics(): array
    {
        $s = $this->db->query(
            'SELECT COUNT(*) AS active_count, COUNT(DISTINCT l.id) AS active_loan_count,
                    COUNT(DISTINCT CASE WHEN li.due_date < CURRENT_DATE THEN l.id END) AS overdue_loan_count,
                    COALESCE(SUM(li.due_date < CURRENT_DATE),0) AS overdue_count
             FROM loan_items li JOIN loans l ON l.id = li.loan_id
             JOIN readers r ON r.id = l.reader_id JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE li.returned_at IS NULL AND '.self::LOAN_FILTER
        );
        return $s->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function categoryStats(): array
    {
        return $this->db->query(
            'SELECT c.id, c.name, COUNT(DISTINCT b.id) AS title_count,
                    COALESCE(SUM(cp.id IS NOT NULL AND cp.status <> "retired"), 0) AS copy_count
             FROM categories c JOIN books b ON b.category_id = c.id AND b.is_active = 1
                  AND '.self::BOOK_FILTER.'
             LEFT JOIN book_copies cp ON cp.book_id = b.id
             GROUP BY c.id,c.name ORDER BY title_count DESC,c.name'
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filteredMonthly(string $from, string $to): array
    {
        $s = $this->db->prepare(
            'SELECT DATE_FORMAT(li.borrowed_at, "%Y-%m") AS month_key, COUNT(*) AS loan_count
             FROM loan_items li JOIN loans l ON l.id = li.loan_id
             JOIN readers r ON r.id = l.reader_id JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE li.borrowed_at >= ? AND li.borrowed_at < DATE_ADD(?, INTERVAL 1 DAY)
               AND '.self::LOAN_FILTER.' GROUP BY month_key ORDER BY month_key'
        );
        $s->execute([$from,$to]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function monthlySummary(string $from, string $to): array
    {
        $s = $this->db->prepare(
            'SELECT DATE_FORMAT(li.borrowed_at, "%Y-%m") AS month_key,
                    COUNT(*) AS borrowed_count,
                    COALESCE(SUM(li.returned_at IS NOT NULL),0) AS returned_count,
                    COALESCE(SUM(li.returned_at IS NULL AND li.due_date < CURRENT_DATE),0) AS overdue_count
             FROM loan_items li JOIN loans l ON l.id = li.loan_id
             JOIN readers r ON r.id = l.reader_id JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE li.borrowed_at >= ? AND li.borrowed_at < DATE_ADD(?, INTERVAL 1 DAY)
               AND '.self::LOAN_FILTER.' GROUP BY month_key ORDER BY month_key'
        );
        $s->execute([$from,$to]);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function popularBooks(int $limit = 8): array
    {
        $limit = max(1,min(20,$limit));
        return $this->db->query(
            'SELECT b.id,b.book_code,b.title,b.author,c.name AS category_name,
                    (SELECT COUNT(*) FROM book_copies cpa WHERE cpa.book_id=b.id) AS total_copies,
                    (SELECT COUNT(*) FROM book_copies cpa WHERE cpa.book_id=b.id AND cpa.status="available") AS available_copies,
                    COUNT(*) AS borrow_count
             FROM loan_items li JOIN loans l ON l.id=li.loan_id
             JOIN readers r ON r.id=l.reader_id JOIN book_copies cp ON cp.id=li.book_copy_id
             JOIN books b ON b.id=cp.book_id LEFT JOIN categories c ON c.id=b.category_id
             WHERE b.is_active=1 AND '.self::LOAN_FILTER.'
             GROUP BY b.id,b.book_code,b.title,b.author,c.name
             ORDER BY borrow_count DESC,b.title LIMIT '.$limit
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function collected(string $from,string $to): float
    {
        $s=$this->db->prepare(
            'SELECT COALESCE(SUM(fp.amount),0) FROM fine_payments fp
             JOIN readers r ON r.id=fp.reader_id
             WHERE fp.created_at >= ? AND fp.created_at < DATE_ADD(?,INTERVAL 1 DAY)
               AND '.self::READER_FILTER
        );
        $s->execute([$from,$to]);
        return (float)$s->fetchColumn();
    }

    public function outstanding(): float
    {
        $s=$this->db->query(
            'SELECT COALESCE(SUM(GREATEST(fc.amount-COALESCE(p.paid,0)-COALESCE(w.waived,0),0)),0)
             FROM fine_charges fc JOIN loan_items li ON li.id=fc.loan_item_id
             JOIN loans l ON l.id=li.loan_id JOIN readers r ON r.id=l.reader_id
             JOIN book_copies cp ON cp.id=li.book_copy_id JOIN books b ON b.id=cp.book_id
             LEFT JOIN (SELECT fine_charge_id,SUM(amount) paid FROM fine_payment_allocations GROUP BY fine_charge_id) p ON p.fine_charge_id=fc.id
             LEFT JOIN (SELECT fine_charge_id,SUM(amount) waived FROM fine_waivers GROUP BY fine_charge_id) w ON w.fine_charge_id=fc.id
             WHERE '.self::LOAN_FILTER
        );
        return (float)$s->fetchColumn();
    }
}
