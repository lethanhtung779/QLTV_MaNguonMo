<?php
declare(strict_types=1);

namespace Library\Services;

use Library\Core\Session;
use Library\Repositories\ActivityRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\UserRepository;
use Library\Services\LoginThrottle;
use PDO;
use RuntimeException;

final class AuthService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function authenticate(string $email, string $password): array
    {
        $throttle = new LoginThrottle();
        $throttle->check($email);
        $attempts = (int) ($_SESSION['_login_attempts'] ?? 0);
        $lockedUntil = (int) ($_SESSION['_login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            throw new RuntimeException('Bạn đã thử đăng nhập quá nhiều lần. Hãy chờ vài phút rồi thử lại.');
        }

        $users = new UserRepository($this->db);
        $user = $users->findByEmail(mb_strtolower(trim($email)));
        if (!$user || !(bool) $user['is_active'] || strlen($password) > 72 || str_contains($password, "\0") || !password_verify($password, $user['password_hash'])) {
            $throttle->failure($email);
            $attempts++;
            $_SESSION['_login_attempts'] = $attempts;
            if ($attempts >= 5) {
                $_SESSION['_login_locked_until'] = time() + 300;
                $_SESSION['_login_attempts'] = 0;
            }
            throw new RuntimeException('Email hoặc mật khẩu không chính xác.');
        }

        if ($user['role'] === 'reader') {
            $reader = (new ReaderRepository($this->db))->findByUser((int) $user['id']);
            if (!$reader) {
                throw new RuntimeException('Tài khoản độc giả chưa có hồ sơ thư viện. Vui lòng liên hệ thủ thư.');
            }
        }

        $throttle->clear($email);
        unset($_SESSION['_login_attempts'], $_SESSION['_login_locked_until']);
        Session::regenerate();
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'credential_version' => hash('sha256', $user['password_hash']),
        ];
        $users->markLogin((int) $user['id']);
        (new ActivityRepository($this->db))->log((int) $user['id'], 'login', 'user', (int) $user['id'], 'Đăng nhập hệ thống');

        if ($user['role'] === 'reader') {
            $_SESSION['user']['reader_id'] = (int) $reader['id'];
        }

        return $_SESSION['user'];
    }

    public function registerReader(string $name, string $email, string $password, string $phone = ''): void
    {
        if (str_contains($password, "\0") || strlen($password) > 72 || strlen($password) < 12 || !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new RuntimeException('Mật khẩu cần tối thiểu 12 ký tự, gồm chữ hoa, chữ thường và chữ số.');
        }

        $this->db->beginTransaction();
        try {
            $users = new UserRepository($this->db);
            if ($users->findByEmail($email)) {
                throw new RuntimeException('Email này đã được đăng ký.');
            }
            $userId = $users->create([
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'reader',
            ]);
            $card = 'DG' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            (new ReaderRepository($this->db))->create([
                'user_id' => $userId,
                'card_number' => $card,
                'full_name' => $name,
                'phone' => $phone ?: null,
                'email' => $email,
                'address' => null,
                'expires_on' => date('Y-m-d', strtotime('+1 year')),
            ]);
            $this->db->commit();
        } catch (\Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }
}
