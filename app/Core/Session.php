<?php
declare(strict_types=1);

namespace Library\Core;

final class Session
{
    public static function start(string $name): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name(preg_replace('/[^a-zA-Z0-9_-]/', '', $name) ?: 'paper_page_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => self::cookiePath(),
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
        $timeout = max(300, min(86400, (int) Config::get('SESSION_TIMEOUT', 1800)));
        if (isset($_SESSION['user'], $_SESSION['_last_activity']) &&
            time() - (int) $_SESSION['_last_activity'] > $timeout) {
            $_SESSION = [];
            self::regenerate();
            $_SESSION['_flash']['error'] = 'Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.';
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function cookiePath(): string
    {
        $base = (string) Config::get('APP_BASE_PATH', '');
        $base = '/' . trim($base, '/');
        return $base === '/' ? '/' : $base . '/';
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
