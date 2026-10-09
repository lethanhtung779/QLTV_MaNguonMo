<?php
declare(strict_types=1);

use Library\Core\Config;
use Library\Core\Database;

require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$email = strtolower(trim((string) Config::get('SEED_ADMIN_EMAIL', '')));
$password = (string) Config::get('SEED_ADMIN_PASSWORD', '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 ||
    str_contains($password, 'ChangeThisAdminPassword')) {
    fwrite(STDERR, "Hãy đặt SEED_ADMIN_EMAIL và mật khẩu mạnh (tối thiểu 12 ký tự) trong .env trước khi chạy.\n");
    exit(1);
}

try {
    $database = Database::connection();
    $statement = $database->prepare(
        'INSERT INTO users (name, email, password_hash, role, is_active)
         VALUES (:name, :email, :password, "admin", 1)
         ON DUPLICATE KEY UPDATE name = VALUES(name), role = "admin", is_active = 1'
    );
    $statement->execute([
        'name' => 'Quản trị viên',
        'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Đã tạo/cập nhật quản trị viên {$email}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Không thể tạo quản trị viên: {$exception->getMessage()}\n");
    exit(1);
}
