<?php
declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

use Library\Core\Config;
use Library\Core\Database;
use Library\Core\Session;

$root = dirname(__DIR__);
Config::load($root . '/.env');
date_default_timezone_set((string) Config::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'));
Session::start((string) Config::get('SESSION_NAME', 'paper_page_session'));

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://covers.openlibrary.org https://images.unsplash.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
}

Database::setConfig([
    'host' => Config::get('DB_HOST', '127.0.0.1'),
    'port' => Config::get('DB_PORT', '3306'),
    'name' => Config::get('DB_NAME', 'paper_page_library'),
    'user' => Config::get('DB_USER', 'root'),
    'password' => Config::get('DB_PASSWORD', ''),
    'charset' => Config::get('DB_CHARSET', 'utf8mb4'),
]);
