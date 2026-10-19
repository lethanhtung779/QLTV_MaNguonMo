<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $exists=$db->query("SHOW COLUMNS FROM loan_items LIKE 'return_condition'")->fetch();
    if (!$exists) $db->exec("ALTER TABLE loan_items ADD return_condition ENUM('good','worn','damaged','lost') NULL AFTER returned_at");
};
