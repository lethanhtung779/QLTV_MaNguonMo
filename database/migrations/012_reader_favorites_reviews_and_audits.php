<?php
declare(strict_types=1);

return static function(PDO $db): void {
    // 1. Bảng lưu sách yêu thích / muốn đọc của độc giả
    $db->exec("
        CREATE TABLE IF NOT EXISTS book_favorites (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reader_id BIGINT UNSIGNED NOT NULL,
            book_id BIGINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reader_book (reader_id, book_id),
            INDEX idx_fav_reader (reader_id),
            INDEX idx_fav_book (book_id),
            CONSTRAINT fk_fav_reader FOREIGN KEY (reader_id) REFERENCES readers(id) ON DELETE CASCADE,
            CONSTRAINT fk_fav_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 2. Bảng đánh giá sao & nhận xét sách
    $db->exec("
        CREATE TABLE IF NOT EXISTS book_reviews (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            book_id BIGINT UNSIGNED NOT NULL,
            reader_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT NOT NULL,
            comment TEXT NULL,
            is_approved TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_reader_book_review (reader_id, book_id),
            INDEX idx_rev_book_approved (book_id, is_approved),
            INDEX idx_rev_reader (reader_id),
            CONSTRAINT fk_rev_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            CONSTRAINT fk_rev_reader FOREIGN KEY (reader_id) REFERENCES readers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 3. Bảng đợt kiểm kê thư viện
    $db->exec("
        CREATE TABLE IF NOT EXISTS inventory_audits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_code VARCHAR(32) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            scope VARCHAR(64) NOT NULL DEFAULT 'all',
            status VARCHAR(32) NOT NULL DEFAULT 'in_progress',
            total_expected INT NOT NULL DEFAULT 0,
            total_scanned INT NOT NULL DEFAULT 0,
            total_matched INT NOT NULL DEFAULT 0,
            total_missing INT NOT NULL DEFAULT 0,
            total_misplaced INT NOT NULL DEFAULT 0,
            total_damaged INT NOT NULL DEFAULT 0,
            started_by BIGINT UNSIGNED NOT NULL,
            approved_by BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_audit_status (status),
            CONSTRAINT fk_audit_started_by FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE RESTRICT,
            CONSTRAINT fk_audit_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // 4. Bảng chi tiết quét bản sao trong đợt kiểm kê
    $db->exec("
        CREATE TABLE IF NOT EXISTS inventory_audit_items (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            audit_id BIGINT UNSIGNED NOT NULL,
            copy_id BIGINT UNSIGNED NOT NULL,
            scanned_by BIGINT UNSIGNED NOT NULL,
            scanned_condition VARCHAR(32) NOT NULL DEFAULT 'good',
            scanned_location VARCHAR(64) NULL,
            is_matched TINYINT(1) NOT NULL DEFAULT 1,
            note VARCHAR(255) NULL,
            scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_audit_copy (audit_id, copy_id),
            INDEX idx_item_audit (audit_id),
            INDEX idx_item_copy (copy_id),
            CONSTRAINT fk_item_audit FOREIGN KEY (audit_id) REFERENCES inventory_audits(id) ON DELETE CASCADE,
            CONSTRAINT fk_item_copy FOREIGN KEY (copy_id) REFERENCES book_copies(id) ON DELETE CASCADE,
            CONSTRAINT fk_item_scanned_by FOREIGN KEY (scanned_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
};
