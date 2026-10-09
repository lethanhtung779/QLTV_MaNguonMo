<?php
declare(strict_types=1);

namespace Library\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static array $config = [];
    private static ?PDO $connection = null;

    public static function setConfig(array $config): void
    {
        self::$config = $config;
        self::$connection = null;
    }

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = self::$config;
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            self::$connection = new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Không thể kết nối MySQL. Hãy kiểm tra dịch vụ, database và cấu hình .env.', 0, $exception);
        }

        return self::$connection;
    }
}
