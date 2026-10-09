<?php
declare(strict_types=1);

namespace Library\Core;

use Library\Controllers\AuthController;
use Library\Controllers\CatalogController;
use Library\Controllers\DashboardController;
use Library\Controllers\FineController;
use Library\Controllers\LoanController;
use Library\Controllers\ManagementController;
use Library\Controllers\NotificationController;
use Library\Controllers\ReaderController;
use RuntimeException;

final class Router
{
    public function dispatch(string $method, string $path): void
    {
        $path = $this->normalizePath($path);
        $route = strtoupper($method) . ' ' . $path;

        if ($route === 'GET /') {
            (new CatalogController())->home();
        } elseif ($route === 'GET /catalog') {
            (new CatalogController())->index();
        } elseif (preg_match('#^GET /book/([1-9][0-9]*)$#', $route, $matches)) {
            (new CatalogController())->show((int) $matches[1]);
        } elseif ($route === 'GET /login') {
            (new AuthController())->loginForm();
        } elseif ($route === 'POST /login') {
            (new AuthController())->login();
        } elseif ($route === 'POST /logout') {
            (new AuthController())->logout();
        } elseif ($route === 'GET /notifications') {
            (new NotificationController())->index();
        } elseif ($route === 'POST /notifications/read') {
            (new NotificationController())->markRead();
        } elseif ($route === 'GET /register') {
            (new AuthController())->registerForm();
        } elseif ($route === 'POST /register') {
            (new AuthController())->register();
        } elseif ($route === 'GET /account' || $route === 'GET /account/loans') {
            (new ReaderController())->dashboard();
        } elseif ($route === 'GET /account/profile') {
            (new ReaderController())->profile();
        } elseif ($route === 'POST /account/profile') {
            (new ReaderController())->updateProfile();
        } elseif ($route === 'POST /account/password') {
            (new ReaderController())->changePassword();
        } elseif ($route === 'POST /account/reservations') {
            (new ReaderController())->reserve();
        } elseif ($route === 'POST /account/reservations/cancel') {
            (new ReaderController())->cancelReservation();
        } elseif ($route === 'POST /account/loans/renew') {
            (new ReaderController())->renewLoan();
        } elseif ($route === 'GET /profile') {
            (new \Library\Controllers\ProfileController())->index();
        } elseif ($route === 'POST /profile' || $route === 'POST /profile/password') {
            (new \Library\Controllers\ProfileController())->save($route === 'POST /profile/password');
        } elseif ($route === 'GET /admin') {
            (new DashboardController())->index();
        } elseif ($route === 'GET /admin/books') {
            (new ManagementController())->books();
        } elseif ($route === 'POST /admin/books') {
            (new ManagementController())->saveBook();
        } elseif ($route === 'POST /admin/books/delete') {
            (new ManagementController())->deleteBook();
        } elseif (preg_match('#^(GET|POST) /admin/books/([1-9][0-9]*)/copies$#', $route, $matches)) {
            $controller = new \Library\Controllers\InventoryController();
            if ($matches[1] === 'GET') $controller->copies((int) $matches[2]);
            else $controller->saveCopy((int) $matches[2]);
        } elseif (preg_match('#^(GET|POST) /admin/(authors|publishers)(/delete)?$#', $route, $matches)) {
            $controller = new \Library\Controllers\InventoryController();
            if ($matches[1] === 'GET' && empty($matches[3])) $controller->metadata($matches[2]);
            elseif ($matches[1] === 'POST') $controller->saveMetadata($matches[2], !empty($matches[3]));
            else { http_response_code(404); View::render('errors/404', ['title'=>'Không tìm thấy trang']); }
        } elseif ($route === 'GET /admin/categories') {
            (new ManagementController())->categories();
        } elseif ($route === 'POST /admin/categories') {
            (new ManagementController())->saveCategory();
        } elseif ($route === 'POST /admin/categories/delete') {
            (new ManagementController())->deleteCategory();
        } elseif (preg_match('#^GET /admin/readers/([1-9][0-9]*)$#', $route, $matches)) {
            (new \Library\Controllers\InventoryController())->reader((int)$matches[1]);
        } elseif ($route === 'GET /admin/readers') {
            (new ManagementController())->readers();
        } elseif ($route === 'POST /admin/readers') {
            (new ManagementController())->saveReader();
        } elseif ($route === 'POST /admin/readers/delete') {
            (new ManagementController())->deleteReader();
        } elseif ($route === 'GET /admin/loans') {
            (new LoanController())->index();
        } elseif ($route === 'GET /admin/fines') {
            (new FineController())->index();
        } elseif ($route === 'POST /admin/fines/payments') {
            (new FineController())->recordPayment();
        } elseif ($route === 'POST /admin/fines/waive') {
            (new FineController())->waive();
        } elseif ($route === 'POST /admin/loans') {
            (new LoanController())->create();
        } elseif ($route === 'POST /admin/loans/return') {
            (new LoanController())->returnBook();
        } elseif ($route === 'POST /admin/loans/renew') {
            (new LoanController())->renew();
        } elseif ($route === 'POST /admin/reservations') {
            (new LoanController())->updateReservation();
        } elseif ($route === 'GET /admin/statistics') {
            (new DashboardController())->statistics();
        } elseif ($route === 'GET /admin/statistics/export') {
            (new DashboardController())->exportCsv();
        } elseif ($route === 'GET /admin/settings') {
            (new ManagementController())->settings();
        } elseif ($route === 'POST /admin/settings') {
            (new ManagementController())->saveSettings();
        } else {
            http_response_code(404);
            View::render(
                'errors/404',
                ['title' => 'Không tìm thấy trang'],
                current_user() ? 'layouts/app' : 'layouts/public'
            );
        }
    }

    private function normalizePath(string $path): string
    {
        $basePath = '/' . trim((string) Config::get('APP_BASE_PATH', ''), '/');
        if ($basePath !== '/' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . trim(rawurldecode(parse_url($path, PHP_URL_PATH) ?: '/'), '/');
        $normalized = rtrim($path, '/');
        return $normalized === '' ? '/' : $normalized;
    }
}
