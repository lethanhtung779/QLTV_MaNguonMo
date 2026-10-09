<?php
declare(strict_types=1);

return static function (PDO $connection): void {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(80) NOT NULL,
            setting_value VARCHAR(500) NOT NULL,
            updated_by BIGINT UNSIGNED NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (setting_key),
            CONSTRAINT fk_system_settings_user FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS loan_renewals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            loan_item_id BIGINT UNSIGNED NOT NULL,
            renewal_number SMALLINT UNSIGNED NOT NULL,
            previous_due_date DATE NOT NULL,
            new_due_date DATE NOT NULL,
            renewed_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_loan_renewals_number (loan_item_id, renewal_number),
            KEY idx_loan_renewals_created (created_at),
            CONSTRAINT fk_loan_renewals_item FOREIGN KEY (loan_item_id) REFERENCES loan_items (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_loan_renewals_user FOREIGN KEY (renewed_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
            CONSTRAINT chk_loan_renewals_dates CHECK (new_due_date > previous_due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            title VARCHAR(180) NOT NULL,
            message VARCHAR(500) NOT NULL,
            type VARCHAR(40) NOT NULL DEFAULT "info",
            entity_type VARCHAR(40) NULL,
            entity_id BIGINT UNSIGNED NULL,
            deduplication_key VARCHAR(190) NULL,
            read_at DATETIME NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_notifications_deduplication (deduplication_key),
            KEY idx_notifications_user_read_created (user_id, read_at, created_at),
            CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columnCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reservations" AND COLUMN_NAME = "book_copy_id"'
    );
    $columnCheck->execute();
    if (!$columnCheck->fetchColumn()) {
        $connection->exec('ALTER TABLE reservations ADD COLUMN book_copy_id BIGINT UNSIGNED NULL AFTER book_id');
    }
    $indexCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reservations" AND INDEX_NAME = "idx_reservations_copy_status"'
    );
    $indexCheck->execute();
    if (!$indexCheck->fetchColumn()) {
        $connection->exec('ALTER TABLE reservations ADD KEY idx_reservations_copy_status (book_copy_id, status)');
    }
    $constraintCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = "fk_reservations_copy"'
    );
    $constraintCheck->execute();
    if (!$constraintCheck->fetchColumn()) {
        $connection->exec(
            'ALTER TABLE reservations ADD CONSTRAINT fk_reservations_copy
             FOREIGN KEY (book_copy_id) REFERENCES book_copies (id) ON UPDATE RESTRICT ON DELETE RESTRICT'
        );
    }

    $setting = $connection->prepare(
        'INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (:key, :value)'
    );
    foreach ([
        'library_name' => 'Paper & Page',
        'max_books_per_reader' => '5',
        'default_loan_days' => '14',
        'max_renewals' => '2',
        'renewal_days' => '14',
        'fine_per_overdue_day' => (string) \Library\Core\Config::get('DAILY_FINE', 5000),
        'reservation_expiry_days' => '3',
    ] as $key => $value) {
        $setting->execute(['key' => $key, 'value' => $value]);
    }
};
