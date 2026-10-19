<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;
use RuntimeException;

final class InventoryAuditRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function createAudit(string $title, string $scope, int $startedBy, ?string $notes = null): array
    {
        $code = 'KK' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        // Count expected active copies based on scope
        $where = ["bc.status NOT IN ('retired', 'lost')", "b.book_code NOT LIKE 'DEMO-%'"];
        $params = [];

        if (str_starts_with($scope, 'shelf:')) {
            $shelfName = substr($scope, 6);
            $where[] = "bc.shelf_location = :shelf";
            $params['shelf'] = $shelfName;
        } elseif (str_starts_with($scope, 'cat:')) {
            $catId = (int) substr($scope, 4);
            $where[] = "b.category_id = :cat";
            $params['cat'] = $catId;
        }

        $whereSql = implode(' AND ', $where);
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM book_copies bc
            JOIN books b ON b.id = bc.book_id
            WHERE {$whereSql}
        ");
        $stmt->execute($params);
        $expected = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare('
            INSERT INTO inventory_audits (
                audit_code, title, scope, status, total_expected,
                total_scanned, total_matched, total_missing, total_misplaced, total_damaged,
                started_by, notes, started_at
            ) VALUES (?, ?, ?, "in_progress", ?, 0, 0, ?, 0, 0, ?, ?, NOW())
        ');
        $stmt->execute([$code, $title, $scope, $expected, $expected, $startedBy, $notes]);
        $id = (int) $this->db->lastInsertId();

        return $this->findAudit($id) ?? throw new RuntimeException('Không thể tạo đợt kiểm kê.');
    }

    public function listAudits(): array
    {
        $stmt = $this->db->query('
            SELECT ia.*, u.name AS starter_name, u2.name AS approver_name
            FROM inventory_audits ia
            LEFT JOIN users u ON u.id = ia.started_by
            LEFT JOIN users u2 ON u2.id = ia.approved_by
            ORDER BY ia.created_at DESC
        ');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findAudit(int $id): ?array
    {
        $stmt = $this->db->prepare('
            SELECT ia.*, u.name AS starter_name, u2.name AS approver_name
            FROM inventory_audits ia
            LEFT JOIN users u ON u.id = ia.started_by
            LEFT JOIN users u2 ON u2.id = ia.approved_by
            WHERE ia.id = ?
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function scanCopy(int $auditId, string $codeOrBarcode, int $scannedBy, string $condition = 'good', ?string $location = null): array
    {
        $code = trim($codeOrBarcode);
        if ($code === '') {
            throw new RuntimeException('Vui lòng nhập hoặc quét mã bản sao/barcode.');
        }

        // Find copy
        $stmt = $this->db->prepare('
            SELECT bc.*, b.title AS book_title, b.book_code, b.category_id
            FROM book_copies bc
            JOIN books b ON b.id = bc.book_id
            WHERE bc.copy_code = ? OR bc.barcode = ?
            LIMIT 1
        ');
        $stmt->execute([$code, $code]);
        $copy = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$copy) {
            throw new RuntimeException('Không tìm thấy bản sao nào khớp với mã: ' . $code);
        }

        $copyId = (int) $copy['id'];
        $audit = $this->findAudit($auditId);
        $scope = (string) ($audit['scope'] ?? 'all');
        $outOfScope = false;
        if (str_starts_with($scope, 'shelf:')) {
            $expectedShelf = substr($scope, 6);
            if ($copy['shelf_location'] !== $expectedShelf) {
                $outOfScope = true;
            }
        } elseif (str_starts_with($scope, 'cat:')) {
            $expectedCatId = (int) substr($scope, 4);
            if ((int) ($copy['category_id'] ?? 0) !== $expectedCatId) {
                $outOfScope = true;
            }
        }

        $isMatched = ($location === null || $location === '' || $location === $copy['shelf_location']) ? 1 : 0;
        if ($outOfScope) {
            $isMatched = 0;
        }

        $noteParts = [];
        if ($outOfScope) {
            $noteParts[] = 'Ngoài phạm vi đợt kiểm kê';
        }
        if (!$isMatched && $location !== null && $location !== '' && $location !== $copy['shelf_location']) {
            $noteParts[] = 'Lệch vị trí kệ (kệ thực tế: ' . $location . ')';
        }
        if (($copy['status'] ?? '') === 'on_loan') {
            $noteParts[] = 'Sách đang ghi nhận mượn';
        }
        if ($condition !== $copy['item_condition']) {
            $noteParts[] = 'Tình trạng thay đổi (' . $copy['item_condition'] . ' -> ' . $condition . ')';
        }
        $scanNote = $noteParts ? implode('; ', $noteParts) : null;

        $this->db->beginTransaction();
        try {
            // Check if already scanned in this audit
            $check = $this->db->prepare('SELECT id FROM inventory_audit_items WHERE audit_id = ? AND copy_id = ?');
            $check->execute([$auditId, $copyId]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Update existing scan record
                $upd = $this->db->prepare('
                    UPDATE inventory_audit_items
                    SET scanned_by = ?, scanned_condition = ?, scanned_location = ?, is_matched = ?, note = ?, scanned_at = NOW()
                    WHERE id = ?
                ');
                $upd->execute([$scannedBy, $condition, $location ?: $copy['shelf_location'], $isMatched, $scanNote, $existing['id']]);
            } else {
                // Insert scan record
                $ins = $this->db->prepare('
                    INSERT INTO inventory_audit_items (audit_id, copy_id, scanned_by, scanned_condition, scanned_location, is_matched, note, scanned_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ');
                $ins->execute([$auditId, $copyId, $scannedBy, $condition, $location ?: $copy['shelf_location'], $isMatched, $scanNote]);
            }

            // Recalculate totals for audit
            $this->recalculateAuditTotals($auditId);

            $this->db->commit();
            return [
                'copy' => $copy,
                'scanned_condition' => $condition,
                'scanned_location' => $location ?: $copy['shelf_location'],
                'is_matched' => (bool) $isMatched,
                'out_of_scope' => (bool) $outOfScope,
                'already_scanned' => (bool) $existing,
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function completeAudit(int $auditId, int $approvedBy, ?string $notes = null): array
    {
        $this->recalculateAuditTotals($auditId);
        $stmt = $this->db->prepare('
            UPDATE inventory_audits
            SET status = "completed", approved_by = ?, completed_at = NOW(), notes = COALESCE(CONCAT(notes, "\n", ?), ?)
            WHERE id = ?
        ');
        $stmt->execute([$approvedBy, $notes, $notes, $auditId]);
        return $this->findAudit($auditId) ?? throw new RuntimeException('Không tìm thấy đợt kiểm kê.');
    }

    public function auditItems(int $auditId): array
    {
        $stmt = $this->db->prepare('
            SELECT iai.*, bc.copy_code, bc.barcode, bc.shelf_location AS expected_location,
                   bc.item_condition AS expected_condition, bc.status AS copy_status,
                   b.title AS book_title, b.book_code, u.name AS scanner_name
            FROM inventory_audit_items iai
            JOIN book_copies bc ON bc.id = iai.copy_id
            JOIN books b ON b.id = bc.book_id
            JOIN users u ON u.id = iai.scanned_by
            WHERE iai.audit_id = ?
            ORDER BY iai.scanned_at DESC
        ');
        $stmt->execute([$auditId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function missingCopies(int $auditId): array
    {
        $audit = $this->findAudit($auditId);
        $scope = (string) ($audit['scope'] ?? 'all');

        $where = [
            'bc.status NOT IN ("retired", "lost")',
            'b.book_code NOT LIKE "DEMO-%"',
            'bc.id NOT IN (SELECT copy_id FROM inventory_audit_items WHERE audit_id = :audit_id)'
        ];
        $params = ['audit_id' => $auditId];

        if (str_starts_with($scope, 'shelf:')) {
            $where[] = 'bc.shelf_location = :shelf';
            $params['shelf'] = substr($scope, 6);
        } elseif (str_starts_with($scope, 'cat:')) {
            $where[] = 'b.category_id = :cat';
            $params['cat'] = (int) substr($scope, 4);
        }

        $whereSql = implode(' AND ', $where);

        $stmt = $this->db->prepare("
            SELECT bc.*, b.title AS book_title, b.book_code, b.author,
                   (CASE WHEN bc.status = 'on_loan' THEN 1 ELSE 0 END) AS is_on_loan
            FROM book_copies bc
            JOIN books b ON b.id = bc.book_id
            WHERE {$whereSql}
            ORDER BY is_on_loan ASC, bc.shelf_location, b.title
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function recalculateAuditTotals(int $auditId): void
    {
        $totalScanned = (int) $this->db->query("SELECT COUNT(*) FROM inventory_audit_items WHERE audit_id = {$auditId}")->fetchColumn();
        $totalMatched = (int) $this->db->query("SELECT COUNT(*) FROM inventory_audit_items WHERE audit_id = {$auditId} AND is_matched = 1")->fetchColumn();
        $totalMisplaced = (int) $this->db->query("SELECT COUNT(*) FROM inventory_audit_items WHERE audit_id = {$auditId} AND is_matched = 0")->fetchColumn();
        $totalDamaged = (int) $this->db->query("SELECT COUNT(*) FROM inventory_audit_items WHERE audit_id = {$auditId} AND scanned_condition IN ('damaged', 'lost')")->fetchColumn();

        $expected = (int) $this->db->query("SELECT total_expected FROM inventory_audits WHERE id = {$auditId}")->fetchColumn();
        $missing = max(0, $expected - $totalScanned);

        $upd = $this->db->prepare('
            UPDATE inventory_audits
            SET total_scanned = ?, total_matched = ?, total_misplaced = ?, total_damaged = ?, total_missing = ?
            WHERE id = ?
        ');
        $upd->execute([$totalScanned, $totalMatched, $totalMisplaced, $totalDamaged, $missing, $auditId]);
    }
}
