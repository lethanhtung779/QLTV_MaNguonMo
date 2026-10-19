<?php
declare(strict_types=1);

/**
 * Migration 016: Quarantine Simulated Copies & Clean ISBNs.
 * Prepared for Paper Haven V12.2 Data Integrity Release.
 * 
 * ARCHITECTURAL SAFETY (MySQL DDL Handling):
 * In MySQL, ALTER TABLE causes an implicit commit. To prevent broken transactions,
 * we verify and execute the DDL first (idempotently outside of DML transaction),
 * then execute all DML updates (quarantine copies, update verified ISBNs, nullify unverified ISBNs)
 * inside an atomic ACID transaction.
 */

return static function(PDO $pdo): void {
    // 1. Check if 'pending_audit' is already in book_copies.status enum
    $colStmt = $pdo->query("
        SELECT COLUMN_TYPE 
        FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
          AND TABLE_NAME = 'book_copies' 
          AND COLUMN_NAME = 'status'
    ");
    $colType = (string) $colStmt->fetchColumn();

    if (!str_contains($colType, "'pending_audit'")) {
        // DDL causes implicit commit in MySQL, execute idempotently before DML transaction
        $pdo->exec("
            ALTER TABLE book_copies 
            MODIFY COLUMN status ENUM('available','on_loan','reserved','damaged','lost','retired','pending_audit') 
            COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available'
        ");
    }

    // 2. DML Transaction for data updates
    $pdo->beginTransaction();
    try {
        $datasetFile = __DIR__ . '/data/v12_80_books_dataset.php';
        if (!is_file($datasetFile)) {
            throw new RuntimeException("Dataset file not found: $datasetFile");
        }
        $dataset = require $datasetFile;
        $newCodes = "'" . implode("','", array_column($dataset, 'book_code')) . "'";

        // 2.1 Quarantine 160 simulated copies: status = 'pending_audit'
        $pdo->exec("
            UPDATE book_copies 
            SET status = 'pending_audit', 
                shelf_location = 'Kho tiếp nhận — Chờ kiểm kê' 
            WHERE book_id IN (SELECT id FROM books WHERE book_code IN ($newCodes))
        ");

        // 2.2 Update 11 verified ISBNs
        $verifiedIsbns = [
            'B105' => '978-604-2-03054-0', // Dế Mèn phiêu lưu ký - NXB Kim Đồng
            'B110' => '978-604-1-00069-8', // Tôi thấy hoa vàng trên cỏ xanh - NXB Trẻ
            'B203' => '978-0-13-235088-4', // Clean Code - Prentice Hall
            'B204' => '978-0-201-63361-0', // Design Patterns - Addison-Wesley
            'B210' => '978-0-262-03384-8', // Introduction to Algorithms - MIT Press
            'B301' => '978-604-1-01204-2', // Lược sử thời gian - NXB Trẻ
            'B405' => '978-604-77-5576-9', // Tư duy nhanh và chậm - Alpha Books / NXB Thế Giới
            'B504' => '978-604-77-8931-3', // Súng, vi trùng và thép - Omega Plus / NXB Thế Giới
            'B509' => '978-604-392-282-0', // 21 bài học cho thế kỷ 21 - Nhã Nam / NXB Thế Giới
            'B603' => '978-604-335-644-1', // Đi tìm lẽ sống - First News / NXB Tổng hợp TP.HCM
            'B703' => '978-604-2-35413-4', // Hoàng tử bé - NXB Kim Đồng
        ];

        $stmtUpdate = $pdo->prepare("UPDATE books SET isbn = ? WHERE book_code = ?");
        foreach ($verifiedIsbns as $code => $realIsbn) {
            $stmtUpdate->execute([$realIsbn, $code]);
        }

        // 2.3 Set 69 unverified simulated ISBNs to NULL
        $verifiedCodes = "'" . implode("','", array_keys($verifiedIsbns)) . "'";
        $pdo->exec("
            UPDATE books 
            SET isbn = NULL 
            WHERE book_code IN ($newCodes) AND book_code NOT IN ($verifiedCodes)
        ");

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
};
