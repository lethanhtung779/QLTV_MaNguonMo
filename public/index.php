<?php
declare(strict_types=1);

use Library\Core\Config;
use Library\Core\Router;
use Library\Core\View;

require_once dirname(__DIR__) . '/bootstrap/app.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI === 'cli-server') {
    $base = '/' . trim((string) Config::get('APP_BASE_PATH', ''), '/');
    $relative = $base !== '/' && ($requestPath === $base || str_starts_with($requestPath, $base . '/'))
        ? substr($requestPath, strlen($base)) : $requestPath;
    $file = __DIR__ . '/' . ltrim(rawurldecode($relative), '/');
    $realFile = realpath($file);
    if ($relative !== '/' && $realFile !== false &&
        str_starts_with($realFile, realpath(__DIR__) . DIRECTORY_SEPARATOR) && is_file($realFile)) {
        return false;
    }
}

try {
    (new Router())->dispatch(
        $_SERVER['REQUEST_METHOD'] ?? 'GET',
        $requestPath
    );
} catch (Throwable $exception) {
    $logDirectory = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0770, true) && !is_dir($logDirectory)) {
        error_log('Unable to create application log directory.');
    }
    error_log(sprintf(
        "[%s] %s %s: %s in %s:%d\n",
        date(DATE_ATOM),
        $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        $requestPath,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ), 3, $logDirectory . '/app.log');

    http_response_code(500);
    $debug = Config::bool('APP_DEBUG', false) && Config::get('APP_ENV', 'production') !== 'production';
    // The staff layout reads permissions from MySQL; error rendering must not need it.
    View::render('errors/500', [
        'title' => 'Có lỗi xảy ra',
        'debugMessage' => $debug ? $exception->getMessage() : null,
    ], 'layouts/public');
}
