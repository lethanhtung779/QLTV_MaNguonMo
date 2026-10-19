<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS mutation_requests (
        actor_id BIGINT UNSIGNED NOT NULL,
        operation VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        request_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        payload_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        result_json TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(actor_id, operation, request_key),
        CONSTRAINT fk_mutation_actor FOREIGN KEY(actor_id) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if (!$db->query("SHOW INDEX FROM loan_items WHERE Key_name='idx_loan_items_borrowed_at'")->fetch()) {
        $db->exec('ALTER TABLE loan_items ADD INDEX idx_loan_items_borrowed_at (borrowed_at)');
    }
};
