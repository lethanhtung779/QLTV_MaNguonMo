<?php
declare(strict_types=1);

/**
 * Read-only Paper Haven fixture/non-fixture audit. No records or identifiers
 * printed. The label "non-demo" DOES NOT constitute provenance verification.
 * Run from project root: php tests/audit_data_provenance_readonly.php
 */
require dirname(__DIR__) . '/bootstrap/app.php';

use Library\Core\Database;

try {
    $db = Database::connection();
    $sql = [
        'books' => <<<'SQL'
SELECT COUNT(*) AS total,
       COALESCE(SUM(book_code LIKE 'DEMO-%'),0) AS tagged_demo,
       COALESCE(SUM(book_code NOT LIKE 'DEMO-%'),0) AS not_tagged_demo,
       COALESCE(SUM(book_code NOT LIKE 'DEMO-%' AND (isbn IS NULL OR isbn = '')),0) AS non_demo_without_isbn,
       COALESCE(SUM(book_code NOT LIKE 'DEMO-%' AND (cover_url IS NULL OR cover_url = '')),0) AS non_demo_without_cover
FROM books
SQL,
        'readers' => <<<'SQL'
SELECT COUNT(*) AS total,
       COALESCE(SUM(card_number LIKE 'DEMO-%'),0) AS tagged_demo,
       COALESCE(SUM(card_number NOT LIKE 'DEMO-%'),0) AS not_tagged_demo
FROM readers
SQL,
        'loan_items' => <<<'SQL'
SELECT COUNT(*) AS total,
       COALESCE(SUM(CASE WHEN b.book_code LIKE 'DEMO-%' OR r.card_number LIKE 'DEMO-%'
                OR COALESCE(l.note,'') LIKE 'DEMO_FIXTURE:%' THEN 1 ELSE 0 END),0) AS tagged_demo,
       COALESCE(SUM(CASE WHEN b.book_code NOT LIKE 'DEMO-%' AND r.card_number NOT LIKE 'DEMO-%'
                AND COALESCE(l.note,'') NOT LIKE 'DEMO_FIXTURE:%' THEN 1 ELSE 0 END),0) AS not_tagged_demo
FROM loan_items li
JOIN loans l ON l.id = li.loan_id
JOIN readers r ON r.id = l.reader_id
JOIN book_copies cp ON cp.id = li.book_copy_id
JOIN books b ON b.id = cp.book_id
SQL,
        'copies' => <<<'SQL'
SELECT COUNT(*) AS total,
       COALESCE(SUM(b.book_code LIKE 'DEMO-%'),0) AS tagged_demo,
       COALESCE(SUM(b.book_code NOT LIKE 'DEMO-%'),0) AS not_tagged_demo,
       COALESCE(SUM(b.book_code NOT LIKE 'DEMO-%' AND cp.status='available'),0) AS non_demo_available,
       COALESCE(SUM(b.book_code NOT LIKE 'DEMO-%' AND cp.status='pending_audit'),0) AS non_demo_pending_audit
FROM book_copies cp JOIN books b ON b.id=cp.book_id
SQL,
    ];
    $out = [
        'warning' => 'Non-demo is only a naming-based exclusion. It does not verify authenticity, rights, circulation, or human activity.',
        'database_write_operations' => 0,
        'metrics' => [],
    ];
    foreach ($sql as $label => $query) {
        $row = $db->query($query)->fetch(PDO::FETCH_ASSOC);
        $out['metrics'][$label] = array_map('intval', $row ?: []);
    }
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, "Audit FAILED. Check DB connection and schema; no data was changed.\n");
    exit(1);
}
