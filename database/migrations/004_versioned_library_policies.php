<?php
declare(strict_types=1);

return static function (PDO $connection): void {
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS library_policy_versions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            library_name VARCHAR(120) NOT NULL,
            max_books_per_reader SMALLINT UNSIGNED NOT NULL,
            default_loan_days SMALLINT UNSIGNED NOT NULL,
            max_renewals SMALLINT UNSIGNED NOT NULL,
            renewal_days SMALLINT UNSIGNED NOT NULL,
            fine_per_overdue_day DECIMAL(10,2) UNSIGNED NOT NULL,
            reservation_expiry_days SMALLINT UNSIGNED NOT NULL,
            updated_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_library_policy_created (created_at),
            CONSTRAINT fk_library_policy_user FOREIGN KEY (updated_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE SET NULL,
            CONSTRAINT chk_library_policy_copy_limit CHECK (max_books_per_reader BETWEEN 1 AND 50),
            CONSTRAINT chk_library_policy_loan_days CHECK (default_loan_days BETWEEN 1 AND 90),
            CONSTRAINT chk_library_policy_renewals CHECK (max_renewals <= 10),
            CONSTRAINT chk_library_policy_renewal_days CHECK (renewal_days BETWEEN 1 AND 90),
            CONSTRAINT chk_library_policy_reservation_days CHECK (reservation_expiry_days BETWEEN 1 AND 30)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $connection->exec(
        'INSERT INTO library_policy_versions
            (library_name, max_books_per_reader, default_loan_days, max_renewals, renewal_days,
             fine_per_overdue_day, reservation_expiry_days)
         SELECT
            MAX(CASE WHEN setting_key = "library_name" THEN setting_value END),
            CAST(MAX(CASE WHEN setting_key = "max_books_per_reader" THEN setting_value END) AS UNSIGNED),
            CAST(MAX(CASE WHEN setting_key = "default_loan_days" THEN setting_value END) AS UNSIGNED),
            CAST(MAX(CASE WHEN setting_key = "max_renewals" THEN setting_value END) AS UNSIGNED),
            CAST(MAX(CASE WHEN setting_key = "renewal_days" THEN setting_value END) AS UNSIGNED),
            CAST(MAX(CASE WHEN setting_key = "fine_per_overdue_day" THEN setting_value END) AS DECIMAL(10,2)),
            CAST(MAX(CASE WHEN setting_key = "reservation_expiry_days" THEN setting_value END) AS UNSIGNED)
         FROM system_settings
         WHERE NOT EXISTS (SELECT 1 FROM library_policy_versions)'
    );

    $columnCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "loan_renewals" AND COLUMN_NAME = :column_name'
    );
    foreach ([
        'policy_version' => 'ALTER TABLE loan_renewals ADD COLUMN policy_version BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER renewal_number',
        'renewal_days' => 'ALTER TABLE loan_renewals ADD COLUMN renewal_days SMALLINT UNSIGNED NOT NULL DEFAULT 14 AFTER policy_version',
    ] as $column => $alter) {
        $columnCheck->execute(['column_name' => $column]);
        if (!$columnCheck->fetchColumn()) {
            $connection->exec($alter);
        }
    }

    $constraintCheck = $connection->prepare(
        'SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = "fk_loan_renewals_policy"'
    );
    $constraintCheck->execute();
    if (!$constraintCheck->fetchColumn()) {
        $connection->exec(
            'ALTER TABLE loan_renewals ADD CONSTRAINT fk_loan_renewals_policy
             FOREIGN KEY (policy_version) REFERENCES library_policy_versions (id) ON UPDATE RESTRICT ON DELETE RESTRICT'
        );
    }
};
