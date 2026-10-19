<?php
declare(strict_types=1);
return static function (PDO $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS permissions (code VARCHAR(64) PRIMARY KEY, label VARCHAR(190) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db->exec('CREATE TABLE IF NOT EXISTS role_permissions (role VARCHAR(32) NOT NULL, permission_code VARCHAR(64) NOT NULL, PRIMARY KEY(role,permission_code), FOREIGN KEY(permission_code) REFERENCES permissions(code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $db->exec('CREATE TABLE IF NOT EXISTS user_permissions (user_id BIGINT UNSIGNED NOT NULL, permission_code VARCHAR(64) NOT NULL, allowed TINYINT(1) NOT NULL DEFAULT 0, PRIMARY KEY(user_id,permission_code), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(permission_code) REFERENCES permissions(code)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $insert=$db->prepare('INSERT IGNORE INTO permissions(code,label) VALUES(?,?)');
    $grant=$db->prepare('INSERT IGNORE INTO role_permissions(role,permission_code) VALUES(?,?)');
    foreach (\Library\Core\Permissions::LABELS as $code=>$label) {
        $insert->execute([$code,$label]); $grant->execute(['admin',$code]);
        if (!in_array($code,\Library\Core\Permissions::ADMIN_ONLY,true)) $grant->execute(['librarian',$code]);
    }
};
