<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class LoanRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(string $query = '', string $status = ''): array
    {
        $sql = $this->headerSelect() . ' WHERE 1 = 1';
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (
                l.loan_code LIKE :search_code OR r.full_name LIKE :search_name OR r.card_number LIKE :search_card
                OR EXISTS (
                    SELECT 1 FROM loan_items searched_item
                    JOIN book_copies searched_copy ON searched_copy.id = searched_item.book_copy_id
                    JOIN books searched_book ON searched_book.id = searched_copy.book_id
                    WHERE searched_item.loan_id = l.id
                      AND (searched_book.title LIKE :search_title OR searched_book.book_code LIKE :search_book
                           OR searched_copy.copy_code LIKE :search_copy)
                )
            )';
            $search = '%' . $query . '%';
            $params = [
                'search_code' => $search,
                'search_name' => $search,
                'search_card' => $search,
                'search_title' => $search,
                'search_book' => $search,
                'search_copy' => $search,
            ];
        }
        if ($status === 'returned') {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM loan_items active_item WHERE active_item.loan_id = l.id AND active_item.returned_at IS NULL)';
        } elseif ($status === 'overdue') {
            $sql .= ' AND EXISTS (SELECT 1 FROM loan_items overdue_item WHERE overdue_item.loan_id = l.id AND overdue_item.returned_at IS NULL AND overdue_item.due_date < CURRENT_DATE)';
        } elseif ($status === 'borrowed') {
            $sql .= ' AND EXISTS (SELECT 1 FROM loan_items borrowed_item WHERE borrowed_item.loan_id = l.id AND borrowed_item.returned_at IS NULL AND borrowed_item.due_date >= CURRENT_DATE)';
        } elseif ($status === 'active') {
            $sql .= ' AND EXISTS (SELECT 1 FROM loan_items active_item WHERE active_item.loan_id = l.id AND active_item.returned_at IS NULL)';
        }
        $sql .= ' GROUP BY l.id, r.id ORDER BY l.borrowed_at DESC, l.id DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function forReader(int $readerId): array
    {
        $statement = $this->db->prepare(
            'SELECT l.*, li.id AS loan_item_id, li.book_copy_id, li.borrowed_at AS item_borrowed_at,
                    li.due_date AS due_at, li.returned_at AS returned_at, li.returned_at AS item_returned_at, li.daily_fine,
                    (SELECT COUNT(*) FROM loan_renewals lr WHERE lr.loan_item_id = li.id) AS renewal_count,
                    b.book_code, b.title AS book_title, cp.copy_code,
                    CASE WHEN li.returned_at IS NOT NULL THEN "returned"
                         WHEN li.due_date < CURRENT_DATE THEN "overdue"
                         ELSE "borrowed" END AS loan_status,
                    GREATEST(DATEDIFF(COALESCE(li.returned_at, CURRENT_DATE), li.due_date), 0) * li.daily_fine AS fine_amount
             FROM loans l
             JOIN loan_items li ON li.loan_id = l.id
             JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE l.reader_id = :reader_id
             ORDER BY l.borrowed_at DESC, l.id DESC, li.id'
        );
        $statement->execute(['reader_id' => $readerId]);
        return $statement->fetchAll();
    }

    public function itemsForLoanIds(array $loanIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $loanIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $statement = $this->db->prepare(
            'SELECT li.*, cp.book_id, cp.copy_code, cp.item_condition, cp.status AS copy_status,
                    (SELECT COUNT(*) FROM loan_renewals lr WHERE lr.loan_item_id = li.id) AS renewal_count,
                    b.title AS book_title, b.book_code,
                    CASE WHEN li.returned_at IS NOT NULL THEN "returned"
                         WHEN li.due_date < CURRENT_DATE THEN "overdue"
                         ELSE "borrowed" END AS loan_status,
                    GREATEST(DATEDIFF(COALESCE(li.returned_at, CURRENT_DATE), li.due_date), 0) * li.daily_fine AS fine_amount
             FROM loan_items li
             JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE li.loan_id IN (' . $placeholders . ')
             ORDER BY li.loan_id, li.id'
        );
        $statement->execute($ids);
        $itemsByLoan = [];
        foreach ($statement->fetchAll() as $item) {
            $itemsByLoan[(int) $item['loan_id']][] = $item;
        }
        return $itemsByLoan;
    }

    public function activeCountForReader(int $readerId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM loans l JOIN loan_items li ON li.loan_id = l.id
             WHERE l.reader_id = :reader_id AND li.returned_at IS NULL'
        );
        $statement->execute(['reader_id' => $readerId]);
        return (int) $statement->fetchColumn();
    }

    public function activeCountForBook(int $bookId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM loan_items li
             JOIN book_copies cp ON cp.id = li.book_copy_id
             WHERE cp.book_id = :book_id AND li.returned_at IS NULL'
        );
        $statement->execute(['book_id' => $bookId]);
        return (int) $statement->fetchColumn();
    }

    public function availableCopyCountForBook(int $bookId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM book_copies WHERE book_id = :book_id AND status = "available"'
        );
        $statement->execute(['book_id' => $bookId]);
        return (int) $statement->fetchColumn();
    }

    public function availableCopies(): array
    {
        return $this->db->query(
            'SELECT cp.id, cp.book_id, cp.copy_code, b.title, b.book_code,
                    cp.status, r.card_number AS reserved_card
             FROM book_copies cp JOIN books b ON b.id = cp.book_id
             LEFT JOIN reservations res ON res.book_copy_id=cp.id AND res.status="ready"
                  AND (res.expires_at IS NULL OR res.expires_at >= NOW())
             LEFT JOIN readers r ON r.id=res.reader_id
             WHERE (cp.status="available" OR (cp.status="reserved" AND res.id IS NOT NULL))
               AND cp.item_condition IN ("good","worn") AND b.is_active=1
             ORDER BY b.title, cp.copy_code'
        )->fetchAll();
    }

    public function hasActiveBookForReader(int $readerId, int $bookId): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM loans l JOIN loan_items li ON li.loan_id = l.id
                JOIN book_copies cp ON cp.id = li.book_copy_id
                WHERE l.reader_id = :reader_id AND cp.book_id = :book_id AND li.returned_at IS NULL
             )'
        );
        $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId]);
        return (bool) $statement->fetchColumn();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO loans (loan_code, reader_id, book_id, issued_by, borrowed_at, due_at, daily_fine, note)
             VALUES (:loan_code, :reader_id, :book_id, :issued_by, :borrowed_at, :due_at, :daily_fine, :note)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function createItem(int $loanId, int $copyId, string $borrowedAt, string $dueAt, float $dailyFine): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO loan_items (loan_id, book_copy_id, borrowed_at, due_date, daily_fine)
             VALUES (:loan_id, :book_copy_id, :borrowed_at, :due_date, :daily_fine)'
        );
        $statement->execute([
            'loan_id' => $loanId,
            'book_copy_id' => $copyId,
            'borrowed_at' => $borrowedAt,
            'due_date' => $dueAt,
            'daily_fine' => $dailyFine,
        ]);
    }

    public function findCopyForUpdate(int $copyId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT cp.id, cp.book_id, cp.copy_code, cp.status, cp.item_condition,
                    b.title, b.is_active AS book_active
             FROM book_copies cp JOIN books b ON b.id = cp.book_id
             WHERE cp.id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $copyId]);
        $copy = $statement->fetch();
        return $copy ?: null;
    }

    public function findItemForUpdate(int $itemId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT li.*, l.loan_code, l.reader_id, cp.book_id, cp.copy_code, cp.status AS copy_status, b.title
             FROM loan_items li
             JOIN loans l ON l.id = li.loan_id
             JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE li.id = :id FOR UPDATE'
        );
        $statement->execute(['id' => $itemId]);
        $item = $statement->fetch();
        return $item ?: null;
    }

    public function renewalCount(int $loanItemId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM loan_renewals WHERE loan_item_id = :item_id');
        $statement->execute(['item_id' => $loanItemId]);
        return (int) $statement->fetchColumn();
    }

    public function hasActiveReservationsForBook(int $bookId): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM reservations WHERE book_id = :book_id AND status IN ("pending", "ready"))'
        );
        $statement->execute(['book_id' => $bookId]);
        return (bool) $statement->fetchColumn();
    }

    public function recordRenewal(
        int $loanItemId,
        int $renewalNumber,
        int $policyVersion,
        int $renewalDays,
        string $oldDueDate,
        string $newDueDate,
        int $userId
    ): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO loan_renewals
             (loan_item_id, renewal_number, policy_version, renewal_days, previous_due_date, new_due_date, renewed_by)
             VALUES (:item_id, :number, :policy_version, :renewal_days, :old_due, :new_due, :user_id)'
        );
        $statement->execute([
            'item_id' => $loanItemId,
            'number' => $renewalNumber,
            'policy_version' => $policyVersion,
            'renewal_days' => $renewalDays,
            'old_due' => $oldDueDate,
            'new_due' => $newDueDate,
            'user_id' => $userId,
        ]);
    }

    public function updateItemDueDate(int $loanItemId, int $loanId, string $newDueDate): void
    {
        $statement = $this->db->prepare('UPDATE loan_items SET due_date = :due_date WHERE id = :item_id AND returned_at IS NULL');
        $statement->execute(['due_date' => $newDueDate, 'item_id' => $loanItemId]);
        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Không thể cập nhật hạn trả của bản sách.');
        }
        $header = $this->db->prepare(
            'UPDATE loans SET due_at = (SELECT MAX(due_date) FROM loan_items WHERE loan_id = :loan_id) WHERE id = :id'
        );
        $header->execute(['loan_id' => $loanId, 'id' => $loanId]);
    }

    public function findAvailableCopyForUpdate(int $bookId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, book_id, copy_code FROM book_copies
             WHERE book_id = :book_id AND status = "available" ORDER BY id LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['book_id' => $bookId]);
        $copy = $statement->fetch();
        return $copy ?: null;
    }

    public function holdCopy(int $copyId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE book_copies SET status = "reserved" WHERE id = :id AND status = "available"'
        );
        $statement->execute(['id' => $copyId]);
        return $statement->rowCount() === 1;
    }

    public function markItemReturned(int $itemId, int $copyId, string $condition): void
    {
        $returned = $this->db->prepare(
            'UPDATE loan_items SET returned_at = CURRENT_DATE WHERE id = :id AND returned_at IS NULL'
        );
        $returned->execute(['id' => $itemId]);
        $status = match ($condition) {
            'damaged' => 'damaged',
            'lost' => 'lost',
            default => 'available',
        };
        $copy = $this->db->prepare(
            'UPDATE book_copies SET item_condition = :condition, status = :status
             WHERE id = :id AND status = "on_loan"'
        );
        $copy->execute(['condition' => $condition, 'status' => $status, 'id' => $copyId]);
        if ($returned->rowCount() !== 1 || $copy->rowCount() !== 1) {
            throw new \RuntimeException('Không thể cập nhật trạng thái trả của bản sao.');
        }
    }

    public function markCopyOnLoan(int $copyId, bool $wasReserved = false): void
    {
        $allowedStatus = $wasReserved ? '("available", "reserved")' : '("available")';
        $statement = $this->db->prepare(
            'UPDATE book_copies SET status = "on_loan" WHERE id = :id AND status IN ' . $allowedStatus
        );
        $statement->execute(['id' => $copyId]);
        if ($statement->rowCount() !== 1) {
            throw new \RuntimeException('Bản sao đã được mượn hoặc không còn khả dụng.');
        }
    }

    public function updateHeaderReturnStatus(int $loanId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE loans SET returned_at = CASE
                WHEN EXISTS (SELECT 1 FROM loan_items WHERE loan_id = :check_id AND returned_at IS NULL)
                THEN NULL ELSE CURRENT_DATE END
             WHERE id = :id'
        );
        $statement->execute(['check_id' => $loanId, 'id' => $loanId]);
        $active = $this->db->prepare('SELECT COUNT(*) FROM loan_items WHERE loan_id = :id AND returned_at IS NULL');
        $active->execute(['id' => $loanId]);
        return (int) $active->fetchColumn() === 0;
    }

    public function summary(): array
    {
        return $this->db->query(
            'SELECT COUNT(*) AS active_count,
             COUNT(DISTINCT loan_id) AS active_loan_count,
             COUNT(DISTINCT CASE WHEN due_date < CURRENT_DATE THEN loan_id END) AS overdue_loan_count,
             COALESCE(SUM(CASE WHEN due_date < CURRENT_DATE THEN 1 ELSE 0 END), 0) AS overdue_count
             FROM loan_items WHERE returned_at IS NULL'
        )->fetch() ?: ['active_count' => 0, 'overdue_count' => 0];
    }

    public function dueFines(int $readerId): float
    {
        $statement = $this->db->prepare(
            'SELECT COALESCE(SUM(GREATEST(DATEDIFF(COALESCE(li.returned_at, CURRENT_DATE), li.due_date), 0) * li.daily_fine), 0)
             FROM loans l JOIN loan_items li ON li.loan_id = l.id WHERE l.reader_id = :reader_id'
        );
        $statement->execute(['reader_id' => $readerId]);
        return (float) $statement->fetchColumn();
    }

    public function recent(int $limit = 6): array
    {
        $limit = max(1, min(30, $limit));
        return $this->db->query(
            $this->headerSelect() . ' GROUP BY l.id, r.id ORDER BY l.created_at DESC, l.id DESC LIMIT ' . $limit
        )->fetchAll();
    }

    public function monthlyCounts(int $months = 6): array
    {
        $months = max(1, min(24, $months));
        $statement = $this->db->query(
            'SELECT DATE_FORMAT(borrowed_at, "%Y-%m") AS month_key, COUNT(*) AS loan_count
             FROM loan_items WHERE borrowed_at >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $months . ' MONTH)
             GROUP BY DATE_FORMAT(borrowed_at, "%Y-%m") ORDER BY month_key'
        );
        return $statement->fetchAll();
    }

    public function reportRows(string $dateFrom, string $dateTo, string $status = '', int $limit = 10001): array
    {
        $limit = max(1, min(10001, $limit));
        $sql = 'SELECT l.loan_code, r.card_number, r.full_name, b.book_code, b.title, cp.copy_code,
                       li.borrowed_at, li.due_date, li.returned_at, li.daily_fine,
                       CASE WHEN li.returned_at IS NOT NULL THEN "returned"
                            WHEN li.due_date < CURRENT_DATE THEN "overdue" ELSE "borrowed" END AS item_status,
                       COALESCE((SELECT SUM(fc.amount) FROM fine_charges fc WHERE fc.loan_item_id = li.id), 0) AS charged_amount,
                       COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a
                                 JOIN fine_charges fc ON fc.id = a.fine_charge_id WHERE fc.loan_item_id = li.id), 0) AS paid_amount,
                       COALESCE((SELECT SUM(w.amount) FROM fine_waivers w
                                 JOIN fine_charges fc ON fc.id = w.fine_charge_id WHERE fc.loan_item_id = li.id), 0) AS waived_amount
                FROM loan_items li
                JOIN loans l ON l.id = li.loan_id
                JOIN readers r ON r.id = l.reader_id
                JOIN book_copies cp ON cp.id = li.book_copy_id
                JOIN books b ON b.id = cp.book_id
                WHERE li.borrowed_at >= :date_from
                  AND li.borrowed_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
        $params = ['date_from' => $dateFrom, 'date_to' => $dateTo];
        if ($status === 'returned') {
            $sql .= ' AND li.returned_at IS NOT NULL';
        } elseif ($status === 'overdue') {
            $sql .= ' AND li.returned_at IS NULL AND li.due_date < CURRENT_DATE';
        } elseif ($status === 'borrowed') {
            $sql .= ' AND li.returned_at IS NULL AND li.due_date >= CURRENT_DATE';
        }
        $sql .= ' ORDER BY li.borrowed_at DESC, li.id DESC LIMIT ' . $limit;
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function updateReservation(int $id, string $status, ?int $userId, ?int $copyId = null, int $expiryDays = 3): bool
    {
        $expiryDays = max(1, min(30, $expiryDays));
        $statement = $this->db->prepare(
            'UPDATE reservations SET status = :status, handled_by = :user_id,
             book_copy_id = CASE WHEN :copy_status = "ready" THEN :copy_id ELSE book_copy_id END,
             expires_at = CASE WHEN :expiry_status = "ready" THEN DATE_ADD(NOW(), INTERVAL ' . $expiryDays . ' DAY) ELSE expires_at END
             WHERE id = :id AND status IN ("pending", "ready")'
        );
        $statement->execute([
            'id' => $id,
            'status' => $status,
            'user_id' => $userId,
            'copy_status' => $status,
            'expiry_status' => $status,
            'copy_id' => $copyId,
        ]);
        return $statement->rowCount() === 1;
    }

    public function findReservation(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, book_id, reader_id, book_copy_id, status FROM reservations WHERE id = :id');
        $statement->execute(['id' => $id]);
        $reservation = $statement->fetch();
        return $reservation ?: null;
    }

    public function findReservationForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT id, book_id, reader_id, book_copy_id, status FROM reservations WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $id]);
        $reservation = $statement->fetch();
        return $reservation ?: null;
    }

    public function reservations(): array
    {
        return $this->db->query(
            'SELECT res.*, r.card_number, r.full_name, b.book_code, b.title, cp.copy_code
             FROM reservations res JOIN readers r ON r.id = res.reader_id
             JOIN books b ON b.id = res.book_id
             LEFT JOIN book_copies cp ON cp.id = res.book_copy_id
             WHERE res.status IN ("pending", "ready")
             ORDER BY FIELD(res.status, "ready", "pending"), res.reserved_at'
        )->fetchAll();
    }

    public function readerHasReservation(int $readerId, int $bookId): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM reservations WHERE reader_id = :reader_id AND book_id = :book_id AND status IN ("pending", "ready"))'
        );
        $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId]);
        return (bool) $statement->fetchColumn();
    }

    public function pendingReservationsForBook(int $bookId): array
    {
        $statement = $this->db->prepare(
            'SELECT res.id, res.reader_id
             FROM reservations res
             WHERE res.book_id = :book_id AND res.status = "pending"
             ORDER BY res.reserved_at, res.id FOR UPDATE'
        );
        $statement->execute(['book_id' => $bookId]);
        return $statement->fetchAll();
    }

    public function readyReservationForReader(int $readerId, int $bookId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, book_copy_id FROM reservations WHERE reader_id = :reader_id AND book_id = :book_id AND status = "ready"
             AND (expires_at IS NULL OR expires_at >= NOW()) ORDER BY reserved_at, id LIMIT 1 FOR UPDATE'
        );
        $statement->execute(['reader_id' => $readerId, 'book_id' => $bookId]);
        $reservation = $statement->fetch();
        return $reservation ?: null;
    }

    public function fulfillReservation(int $id): void
    {
        $statement = $this->db->prepare('UPDATE reservations SET status = "fulfilled" WHERE id = :id AND status = "ready"');
        $statement->execute(['id' => $id]);
    }

    public function promoteReservation(int $id, ?int $userId, int $copyId, int $expiryDays): bool
    {
        $expiryDays = max(1, min(30, $expiryDays));
        $statement = $this->db->prepare(
            'UPDATE reservations SET status = "ready", handled_by = :user_id, book_copy_id = :copy_id,
             expires_at = DATE_ADD(NOW(), INTERVAL ' . $expiryDays . ' DAY)
             WHERE id = :id AND status = "pending"'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId, 'copy_id' => $copyId]);
        return $statement->rowCount() === 1;
    }

    public function activeReservationsForBook(int $bookId): int
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM reservations WHERE book_id = :book_id AND status IN ("pending", "ready")'
        );
        $statement->execute(['book_id' => $bookId]);
        return (int) $statement->fetchColumn();
    }

    public function hasWaitingReservationsForBook(int $bookId): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(
                SELECT 1 FROM reservations
                WHERE book_id = :book_id
                  AND (status = "pending"
                       OR (status = "ready" AND book_copy_id IS NULL AND (expires_at IS NULL OR expires_at >= NOW())))
             )'
        );
        $statement->execute(['book_id' => $bookId]);
        return (bool) $statement->fetchColumn();
    }

    private function headerSelect(): string
    {
        return 'SELECT l.*, r.full_name, r.card_number,
                COUNT(li.id) AS item_count,
                COALESCE(SUM(li.returned_at IS NULL), 0) AS active_items,
                COALESCE(SUM(li.returned_at IS NULL AND li.due_date < CURRENT_DATE), 0) AS overdue_items,
                GROUP_CONCAT(DISTINCT CONCAT(b.title, " [", cp.copy_code, "]") ORDER BY b.title SEPARATOR ", ") AS book_title,
                CASE WHEN COUNT(li.id) = 0 OR SUM(li.returned_at IS NULL) = 0 THEN "returned"
                     WHEN SUM(li.returned_at IS NULL AND li.due_date < CURRENT_DATE) > 0 THEN "overdue"
                     ELSE "borrowed" END AS loan_status,
                COALESCE(SUM(GREATEST(DATEDIFF(COALESCE(li.returned_at, CURRENT_DATE), li.due_date), 0) * li.daily_fine), 0) AS fine_amount
             FROM loans l
             JOIN readers r ON r.id = l.reader_id
             LEFT JOIN loan_items li ON li.loan_id = l.id
             LEFT JOIN book_copies cp ON cp.id = li.book_copy_id
             LEFT JOIN books b ON b.id = cp.book_id';
    }
}
