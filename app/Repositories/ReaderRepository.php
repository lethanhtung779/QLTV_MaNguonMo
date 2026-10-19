<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class ReaderRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(string $query = '', ?int $limit = null, int $offset = 0, bool $inactive = false, bool $realOnly = false): array
    {
        $sql = 'SELECT r.*, u.email AS account_email,
            COALESCE(loan_stats.active_loans, 0) AS active_loans,
            COALESCE(loan_stats.overdue_loans, 0) AS overdue_loans
            FROM readers r LEFT JOIN users u ON u.id = r.user_id
            LEFT JOIN (
                SELECT l.reader_id,
                       SUM(li.returned_at IS NULL) AS active_loans,
                       SUM(li.returned_at IS NULL AND li.due_date < CURRENT_DATE) AS overdue_loans
                FROM loans l JOIN loan_items li ON li.loan_id = l.id
                GROUP BY l.reader_id
            ) loan_stats ON loan_stats.reader_id = r.id
            WHERE r.is_active = 1';
        $sql = str_replace('r.is_active = 1', 'r.is_active = ' . ($inactive ? '0' : '1'), $sql);
        if ($realOnly) {
            $sql .= " AND r.card_number NOT LIKE 'DEMO-%' AND r.card_number NOT LIKE 'READ-%'";
        }
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (r.card_number LIKE :search_card OR r.full_name LIKE :search_name OR r.email LIKE :search_email OR r.phone LIKE :search_phone)';
            $search = '%' . $query . '%';
            $params = [
                'search_card' => $search,
                'search_name' => $search,
                'search_email' => $search,
                'search_phone' => $search,
            ];
        }
        $sql .= ' ORDER BY r.full_name, r.id';
        if ($limit !== null) $sql .= ' LIMIT ' . max(1, min(100, $limit)) . ' OFFSET ' . max(0, $offset);
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function countSearch(string $query = '', bool $inactive = false): int
    {
        $sql = 'SELECT COUNT(*) FROM readers WHERE is_active=' . ($inactive ? '0' : '1');
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (card_number LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $params = array_fill(0, 4, '%' . $query . '%');
        }
        $stmt = $this->db->prepare($sql); $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM readers WHERE id = :id AND is_active = 1');
        $statement->execute(['id' => $id]);
        $reader = $statement->fetch();
        return $reader ?: null;
    }

    public function findForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM readers WHERE id = :id AND is_active = 1 FOR UPDATE');
        $statement->execute(['id' => $id]);
        $reader = $statement->fetch();
        return $reader ?: null;
    }

    public function findByUser(int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM readers WHERE user_id = :user_id AND is_active = 1');
        $statement->execute(['user_id' => $userId]);
        $reader = $statement->fetch();
        return $reader ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO readers (user_id, card_number, full_name, phone, email, address, expires_on)
             VALUES (:user_id, :card_number, :full_name, :phone, :email, :address, :expires_on)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $statement = $this->db->prepare(
            'UPDATE readers SET card_number = :card_number, full_name = :full_name, phone = :phone,
             email = :email, address = :address, expires_on = :expires_on WHERE id = :id'
        );
        $statement->execute([
            'id' => $id,
            'card_number' => $data['card_number'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['address'],
            'expires_on' => $data['expires_on'],
        ]);
    }

    public function deactivate(int $id): void
    {
        $statement = $this->db->prepare('UPDATE readers SET is_active = 0 WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function hasActiveLoans(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM loans l JOIN loan_items li ON li.loan_id = l.id
             WHERE l.reader_id = :id AND li.returned_at IS NULL)'
        );
        $statement->execute(['id' => $id]);
        return (bool) $statement->fetchColumn();
    }

    public function hasHistory(int $id): bool
    {
        $statement = $this->db->prepare(
            'SELECT EXISTS(SELECT 1 FROM loans WHERE reader_id = :loan_id)
             OR EXISTS(SELECT 1 FROM reservations WHERE reader_id = :reservation_id)'
        );
        $statement->execute(['loan_id' => $id, 'reservation_id' => $id]);
        return (bool) $statement->fetchColumn();
    }
}
