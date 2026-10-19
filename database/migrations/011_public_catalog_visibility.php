<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $q = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='books' AND COLUMN_NAME='is_public'");
    if (!(int) $q->fetchColumn()) {
        $db->exec('ALTER TABLE books ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active, ADD INDEX idx_books_public_active (is_public, is_active)');
    }
};
