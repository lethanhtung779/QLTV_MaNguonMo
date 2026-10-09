<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Library\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/functions.php';
