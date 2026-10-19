<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class FineRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function overdueReaderIds(): array
    {
        return array_map(
            'intval',
            $this->db->query(
                'SELECT DISTINCT l.reader_id
                 FROM loans l JOIN loan_items li ON li.loan_id = l.id
                 WHERE li.due_date < COALESCE(li.returned_at, CURRENT_DATE)'
            )->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function overdueItems(?int $readerId = null): array
    {
        $statement = $this->db->prepare(
            'SELECT li.id, li.due_date, li.daily_fine, li.returned_at, l.reader_id,
                    b.title, cp.copy_code,
                    GREATEST(DATEDIFF(COALESCE(li.returned_at, CURRENT_DATE), li.due_date), 0) AS overdue_days
             FROM loans l
             JOIN loan_items li ON li.loan_id = l.id
             JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE ' . ($readerId === null ? '' : 'l.reader_id = :reader_id AND ') . '
               li.due_date < COALESCE(li.returned_at, CURRENT_DATE)
             ORDER BY li.id'
        );
        $statement->execute($readerId === null ? [] : ['reader_id' => $readerId]);
        return $statement->fetchAll();
    }

    public function upsertCharge(
        int $loanItemId,
        int $readerId,
        string $type,
        string $amount,
        string $description,
        string $key,
        ?int $assessedBy
    ): void {
        $statement = $this->db->prepare(
            'INSERT INTO fine_charges
             (loan_item_id, reader_id, charge_type, amount, description, deduplication_key, assessed_by)
             VALUES (:loan_item_id, :reader_id, :charge_type, :amount, :description, :deduplication_key, :assessed_by)
             ON DUPLICATE KEY UPDATE
                amount = GREATEST(amount, VALUES(amount)),
                description = VALUES(description),
                assessed_by = COALESCE(VALUES(assessed_by), assessed_by)'
        );
        $statement->execute([
            'loan_item_id' => $loanItemId,
            'reader_id' => $readerId,
            'charge_type' => $type,
            'amount' => $amount,
            'description' => mb_substr($description, 0, 300),
            'deduplication_key' => $key,
            'assessed_by' => $assessedBy,
        ]);
    }

    public function charge(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM fine_charges WHERE id = :id');
        $statement->execute(['id' => $id]);
        $charge = $statement->fetch();
        return $charge ?: null;
    }

    public function chargesForReader(int $readerId): array { return $this->charges($readerId); }
    public function chargesForAll(): array { return $this->charges(null); }

    private function charges(?int $readerId): array
    {
        $statement = $this->db->prepare(
            'SELECT fc.*, b.title, cp.copy_code,
                    COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.fine_charge_id = fc.id), 0) AS paid_amount,
                    COALESCE((SELECT SUM(w.amount) FROM fine_waivers w WHERE w.fine_charge_id = fc.id), 0) AS waived_amount,
                    GREATEST(fc.amount
                        - COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.fine_charge_id = fc.id), 0)
                        - COALESCE((SELECT SUM(w.amount) FROM fine_waivers w WHERE w.fine_charge_id = fc.id), 0), 0) AS balance
             FROM fine_charges fc
             JOIN loan_items li ON li.id = fc.loan_item_id
             JOIN book_copies cp ON cp.id = li.book_copy_id
             JOIN books b ON b.id = cp.book_id
             WHERE ' . ($readerId === null ? '1=1' : 'fc.reader_id = :reader_id') . '
             ORDER BY fc.created_at DESC, fc.id DESC'
        );
        $statement->execute($readerId === null ? [] : ['reader_id' => $readerId]);
        return $statement->fetchAll();
    }

    public function outstandingChargesForUpdate(int $readerId): array
    {
        $statement = $this->db->prepare(
            'SELECT fc.id, fc.amount,
                    GREATEST(fc.amount
                        - COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.fine_charge_id = fc.id), 0)
                        - COALESCE((SELECT SUM(w.amount) FROM fine_waivers w WHERE w.fine_charge_id = fc.id), 0), 0) AS balance
             FROM fine_charges fc
             WHERE fc.reader_id = :reader_id
             ORDER BY fc.created_at, fc.id
             FOR UPDATE'
        );
        $statement->execute(['reader_id' => $readerId]);
        return array_values(array_filter(
            $statement->fetchAll(),
            static fn(array $charge): bool => (float) $charge['balance'] > 0
        ));
    }

    public function accounts(): array
    {
        return $this->db->query(
            'SELECT r.id, r.card_number, r.full_name, r.email,
                    COALESCE(SUM(GREATEST(fc.amount
                        - COALESCE(paid.total, 0)
                        - COALESCE(waived.total, 0), 0)), 0) AS balance,
                    COUNT(DISTINCT CASE WHEN fc.id IS NOT NULL AND fc.amount > COALESCE(paid.total, 0) + COALESCE(waived.total, 0)
                        THEN fc.id END) AS open_charges
             FROM readers r
             LEFT JOIN fine_charges fc ON fc.reader_id = r.id
             LEFT JOIN (
                 SELECT fine_charge_id, SUM(amount) AS total
                 FROM fine_payment_allocations GROUP BY fine_charge_id
             ) paid ON paid.fine_charge_id = fc.id
             LEFT JOIN (
                 SELECT fine_charge_id, SUM(amount) AS total
                 FROM fine_waivers GROUP BY fine_charge_id
             ) waived ON waived.fine_charge_id = fc.id
             WHERE r.is_active = 1
             GROUP BY r.id, r.card_number, r.full_name, r.email
             HAVING balance > 0
             ORDER BY balance DESC, r.full_name'
        )->fetchAll();
    }

    public function readers(): array
    {
        return $this->db->query(
            'SELECT id, card_number, full_name FROM readers WHERE is_active = 1 ORDER BY full_name'
        )->fetchAll();
    }

    public function payments(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        return $this->db->query(
            'SELECT p.*, r.card_number, r.full_name, u.name AS receiver_name
             FROM fine_payments p
             JOIN readers r ON r.id = p.reader_id
             LEFT JOIN users u ON u.id = p.received_by
             ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $limit
        )->fetchAll();
    }

    public function paymentsForReader(int $readerId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->db->prepare(
            'SELECT p.*, u.name AS receiver_name,
                    COALESCE((SELECT SUM(a.amount) FROM fine_payment_allocations a WHERE a.payment_id = p.id), 0) AS allocated_amount
             FROM fine_payments p
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.reader_id = :reader_id
             ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $limit
        );
        $statement->execute(['reader_id' => $readerId]);
        return $statement->fetchAll();
    }

    public function insertPayment(int $readerId, string $amount, string $method, ?string $reference, ?string $note, ?int $userId): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO fine_payments (reader_id, amount, method, reference_code, note, received_by)
             VALUES (:reader_id, :amount, :method, :reference, :note, :received_by)'
        );
        $statement->execute([
            'reader_id' => $readerId,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'note' => $note,
            'received_by' => $userId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function allocate(int $paymentId, int $chargeId, string $amount): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO fine_payment_allocations (payment_id, fine_charge_id, amount)
             VALUES (:payment_id, :charge_id, :amount)'
        );
        $statement->execute(['payment_id' => $paymentId, 'charge_id' => $chargeId, 'amount' => $amount]);
    }

    public function waive(int $chargeId, string $amount, string $reason, int $userId): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO fine_waivers (fine_charge_id, amount, reason, waived_by)
             VALUES (:charge_id, :amount, :reason, :user_id)'
        );
        $statement->execute([
            'charge_id' => $chargeId,
            'amount' => $amount,
            'reason' => $reason,
            'user_id' => $userId,
        ]);
    }

    public function readerIdForCharge(int $chargeId): ?int
    {
        $statement = $this->db->prepare('SELECT reader_id FROM fine_charges WHERE id = :id');
        $statement->execute(['id' => $chargeId]);
        $readerId = $statement->fetchColumn();
        return $readerId === false ? null : (int) $readerId;
    }
}
