<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $manifestPath = dirname(__DIR__) . '/book_metadata_manifest.json';
    if (!is_file($manifestPath)) {
        return;
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];

    // 1. Update B006
    if (isset($manifest['B006'])) {
        $meta = $manifest['B006'];
        $stmt = $db->prepare("
            UPDATE books 
            SET cover_url = '/assets/book-covers/b006-vat-ly-ly-thu.jpg',
                isbn = '9786040182845',
                publisher = 'Nhà xuất bản Giáo dục Việt Nam',
                publication_year = 2019,
                description = :description,
                updated_at = NOW()
            WHERE book_code = 'B006'
        ");
        $stmt->execute([':description' => $meta['rich_description']]);

        // Assign shelf location for physical copies if empty
        $db->exec("
            UPDATE book_copies 
            SET shelf_location = 'Kệ K1-03',
                barcode = IF(barcode IS NULL OR barcode = '', copy_code, barcode)
            WHERE book_id = (SELECT id FROM books WHERE book_code = 'B006' LIMIT 1)
              AND (shelf_location IS NULL OR shelf_location = '')
        ");
    }

    // 2. Update B007
    if (isset($manifest['B007'])) {
        $meta = $manifest['B007'];
        $stmt = $db->prepare("
            UPDATE books 
            SET cover_url = '/assets/book-covers/b007-kinh-te-thinh-vuong.jpg',
                isbn = '9786041169388',
                publisher = 'Nhà xuất bản Trẻ',
                publication_year = 2020,
                description = :description,
                updated_at = NOW()
            WHERE book_code = 'B007'
        ");
        $stmt->execute([':description' => $meta['rich_description']]);

        $db->exec("
            UPDATE book_copies 
            SET shelf_location = 'Kệ E2-05',
                barcode = IF(barcode IS NULL OR barcode = '', copy_code, barcode)
            WHERE book_id = (SELECT id FROM books WHERE book_code = 'B007' LIMIT 1)
              AND (shelf_location IS NULL OR shelf_location = '')
        ");
    }

    // 3. Update B009
    if (isset($manifest['B009'])) {
        $meta = $manifest['B009'];
        $stmt = $db->prepare("
            UPDATE books 
            SET cover_url = '/assets/book-covers/b009-lap-trinh-co-ban.jpg',
                publisher = 'Nhà xuất bản Khoa học',
                publication_year = 2022,
                description = :description,
                updated_at = NOW()
            WHERE book_code = 'B009'
        ");
        $stmt->execute([':description' => $meta['rich_description']]);

        $db->exec("
            UPDATE book_copies 
            SET shelf_location = 'Kệ T1-02',
                barcode = IF(barcode IS NULL OR barcode = '', copy_code, barcode)
            WHERE book_id = (SELECT id FROM books WHERE book_code = 'B009' LIMIT 1)
              AND (shelf_location IS NULL OR shelf_location = '')
        ");
    }

    // 4. Update rich descriptions for B001 to B005, B008
    $stmtDesc = $db->prepare("
        UPDATE books 
        SET description = :description,
            updated_at = NOW()
        WHERE book_code = :code
    ");

    foreach (['B001', 'B002', 'B003', 'B004', 'B005', 'B008'] as $code) {
        if (isset($manifest[$code]['rich_description'])) {
            $stmtDesc->execute([
                ':code' => $code,
                ':description' => $manifest[$code]['rich_description']
            ]);
        }
    }
};
