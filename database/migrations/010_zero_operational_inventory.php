<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $q=$db->query("SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='chk_books_copies'");
    $clause=$q->fetchColumn();
    if($clause===false){
        $db->exec('ALTER TABLE books ADD CONSTRAINT chk_books_copies CHECK (total_copies >= 0)');
    }elseif(preg_match('/>=\s*1/',$clause)){
        // One atomic ALTER replaces the old minimum; rows and histories are preserved.
        $db->exec('ALTER TABLE books DROP CHECK chk_books_copies,
            ADD CONSTRAINT chk_books_copies CHECK (total_copies >= 0)');
    }
};
