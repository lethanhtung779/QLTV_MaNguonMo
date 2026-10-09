<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Core\Database;
use Library\Core\View;
use Library\Repositories\NotificationRepository;
use PDO;

abstract class Controller
{
    protected readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $user = current_user();
        if ($user && isset($user['id'])) {
            $data['notificationUnreadCount'] ??= (new NotificationRepository($this->db))
                ->unreadCount((int) $user['id']);
        }
        View::render($view, $data, $layout);
    }

    protected function fail(string $message, string $path): never
    {
        flash('error', $message);
        redirect($path);
    }

    protected function success(string $message, string $path): never
    {
        flash('success', $message);
        redirect($path);
    }

    protected function postPath(string $key, string $fallback): string
    {
        $raw = $_POST[$key] ?? '';
        $path = is_string($raw) ? $raw : '';
        if (!preg_match('#^/(?!/)[a-zA-Z0-9/_-]*$#', $path)) {
            return $fallback;
        }
        return $path;
    }
}
