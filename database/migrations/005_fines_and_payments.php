<?php
declare(strict_types=1);

return static function (PDO $connection): void {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS fine_charges (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            loan_item_id BIGINT UNSIGNED NOT NULL,
            reader_id BIGINT UNSIGNED NOT NULL,
            charge_type ENUM("overdue", "damaged", "lost") NOT NULL,
            amount DECIMAL(10,2) UNSIGNED NOT NULL,
            description VARCHAR(300) NOT NULL,
            deduplication_key VARCHAR(190) NOT NULL,
            assessed_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_fine_charges_deduplication (deduplication_key),
            KEY idx_fine_charges_reader_created (reader_id, created_at, id),
            CONSTRAINT fk_fine_charges_item FOREIGN KEY (loan_item_id) REFERENCES loan_items (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_fine_charges_reader FOREIGN KEY (reader_id) REFERENCES readers (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_fine_charges_assessor FOREIGN KEY (assessed_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
            CONSTRAINT chk_fine_charges_amount CHECK (amount >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS fine_payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            reader_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) UNSIGNED NOT NULL,
            method ENUM("cash", "bank_transfer", "card", "other") NOT NULL,
            reference_code VARCHAR(120) NULL,
            note VARCHAR(300) NULL,
            received_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_fine_payments_reader_created (reader_id, created_at, id),
            CONSTRAINT fk_fine_payments_reader FOREIGN KEY (reader_id) REFERENCES readers (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_fine_payments_receiver FOREIGN KEY (received_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
            CONSTRAINT chk_fine_payments_amount CHECK (amount > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS fine_payment_allocations (
            payment_id BIGINT UNSIGNED NOT NULL,
            fine_charge_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) UNSIGNED NOT NULL,
            PRIMARY KEY (payment_id, fine_charge_id),
            KEY idx_fine_payment_allocations_charge (fine_charge_id),
            CONSTRAINT fk_fine_allocations_payment FOREIGN KEY (payment_id) REFERENCES fine_payments (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_fine_allocations_charge FOREIGN KEY (fine_charge_id) REFERENCES fine_charges (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT chk_fine_allocations_amount CHECK (amount > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS fine_waivers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            fine_charge_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) UNSIGNED NOT NULL,
            reason VARCHAR(300) NOT NULL,
            waived_by BIGINT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_fine_waivers_charge (fine_charge_id, created_at),
            CONSTRAINT fk_fine_waivers_charge FOREIGN KEY (fine_charge_id) REFERENCES fine_charges (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT fk_fine_waivers_user FOREIGN KEY (waived_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT chk_fine_waivers_amount CHECK (amount > 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $columnCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
    );
    foreach ([
        ['library_policy_versions', 'damaged_copy_fine', 'ALTER TABLE library_policy_versions ADD COLUMN damaged_copy_fine DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 50000'],
        ['library_policy_versions', 'lost_copy_fine', 'ALTER TABLE library_policy_versions ADD COLUMN lost_copy_fine DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 200000'],
    ] as [$table, $column, $alter]) {
        $columnCheck->execute(['table_name' => $table, 'column_name' => $column]);
        if (!$columnCheck->fetchColumn()) {
            $connection->exec($alter);
        }
    }

    $connection->exec(
        'INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
            ("damaged_copy_fine", "50000"),
            ("lost_copy_fine", "200000")'
    );
    $connection->exec(
        'UPDATE library_policy_versions SET
            damaged_copy_fine = CAST((SELECT setting_value FROM system_settings WHERE setting_key = "damaged_copy_fine") AS DECIMAL(10,2)),
            lost_copy_fine = CAST((SELECT setting_value FROM system_settings WHERE setting_key = "lost_copy_fine") AS DECIMAL(10,2))
         WHERE id = (SELECT current_version FROM (SELECT MAX(id) AS current_version FROM library_policy_versions) current_policy)'
    );
};
