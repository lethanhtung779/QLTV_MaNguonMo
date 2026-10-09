<?php
declare(strict_types=1);

namespace Library\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function valid(mixed $token): bool
    {
        return is_string($token) && isset($_SESSION['_csrf']) &&
            hash_equals((string) $_SESSION['_csrf'], $token);
    }
}
