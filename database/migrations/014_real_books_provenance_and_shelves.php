<?php
declare(strict_types=1);

/**
 * Migration 014: Paper Haven V10 — Real Books Provenance, Shelf Assignment, & Removal of Generated Covers
 * 
 * 1. Assign real shelf locations for all core circulating copies (B001 - B008) in book_copies.
 * 2. Remove generated / artificial cover URLs for B006, B007, B009 (set cover_url = NULL)
 *    to comply strictly with Master Prompt V10 principles (honest neutral placeholders).
 * 3. Ensure barcode matches copy_code where missing.
 * 4. Zero deletion of records, zero truncation, zero foreign key disruption.
 */
return static function(PDO $db): void {
    // 1. Assign shelf locations for all core circulating book copies
    $shelves = [
        'B001' => 'Kệ V1-01', // Văn học
        'B002' => 'Kệ L2-02', // Lịch sử
        'B003' => 'Kệ T1-04', // Tâm lý - Kỹ năng sống
        'B004' => 'Kệ V1-03', // Văn học
        'B005' => 'Kệ N1-05', // Thiếu nhi
        'B006' => 'Kệ K1-03', // Khoa học
        'B007' => 'Kệ E2-05', // Kinh tế
        'B008' => 'Kệ C2-01', // Tin học
        'B009' => 'Kệ T1-02', // Tin học (Demo/Internal)
    ];

    $stmtShelf = $db->prepare("
        UPDATE book_copies 
        SET shelf_location = :shelf,
            barcode = IF(barcode IS NULL OR barcode = '', copy_code, barcode)
        WHERE book_id = (SELECT id FROM books WHERE book_code = :code LIMIT 1)
          AND (shelf_location IS NULL OR shelf_location = '')
    ");

    foreach ($shelves as $code => $shelf) {
        $stmtShelf->execute([':code' => $code, ':shelf' => $shelf]);
    }

    // 2. Remove artificially generated covers for B006, B007, B009 (comply with V10: no AI/generated covers)
    $db->exec("
        UPDATE books 
        SET cover_url = NULL,
            updated_at = NOW()
        WHERE book_code IN ('B006', 'B007', 'B009')
          AND cover_url LIKE '/assets/book-covers/b00%'
    ");

    // 3. For B009: ensure ISBN is NULL (do not fabricate ISBN)
    $db->exec("
        UPDATE books 
        SET isbn = NULL,
            updated_at = NOW()
        WHERE book_code = 'B009'
    ");
};
