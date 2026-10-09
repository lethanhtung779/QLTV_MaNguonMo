<?php
declare(strict_types=1);

use Library\Core\Config;
use Library\Core\Csrf;

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    $base = '/' . trim((string) Config::get('APP_BASE_PATH', ''), '/');
    $base = $base === '/' ? '' : $base;
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Csrf::token()) . '">';
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;
    return is_array($user) ? $user : null;
}

function require_auth(?array $roles = null): array
{
    $user = current_user();
    if ($user === null) {
        flash('error', 'Vui lòng đăng nhập để tiếp tục.');
        redirect('/login');
    }
    $record = (new \Library\Repositories\UserRepository(\Library\Core\Database::connection()))
        ->findById((int) $user['id']);
    if (!$record || $record['role'] !== ($user['role'] ?? '')) {
        unset($_SESSION['user']);
        \Library\Core\Session::regenerate();
        flash('error', 'Tài khoản hoặc quyền truy cập đã thay đổi. Vui lòng đăng nhập lại.');
        redirect('/login');
    }
    if ($roles !== null && !in_array($user['role'] ?? '', $roles, true)) {
        http_response_code(403);
        \Library\Core\View::render('errors/403', ['title' => 'Không đủ quyền truy cập']);
        exit;
    }
    return $user;
}

function validate_csrf(): void
{
    if (!\Library\Core\Csrf::valid($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        \Library\Core\View::render(
            'errors/419',
            ['title' => 'Phiên biểu mẫu đã hết hạn'],
            current_user() ? 'layouts/app' : 'layouts/public'
        );
        exit;
    }
}

function request_string(string $key, int $maxLength = 255, bool $required = false): string
{
    $raw = $_POST[$key] ?? '';
    if (!is_string($raw) && !is_int($raw) && !is_float($raw)) {
        throw new InvalidArgumentException('Định dạng trường dữ liệu không hợp lệ.');
    }
    $value = trim((string) $raw);
    if ($required && $value === '') {
        throw new InvalidArgumentException('Vui lòng nhập đầy đủ các trường bắt buộc.');
    }
    if (mb_strlen($value) > $maxLength) {
        throw new InvalidArgumentException("Trường dữ liệu vượt quá {$maxLength} ký tự.");
    }
    return $value;
}

function request_int(string $key, int $min = 0, int $max = 1000000): int
{
    $raw = $_POST[$key] ?? null;
    if (!is_string($raw) && !is_int($raw)) {
        throw new InvalidArgumentException('Giá trị số không hợp lệ.');
    }
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || $value > $max) {
        throw new InvalidArgumentException('Giá trị số không hợp lệ.');
    }
    return $value;
}

function query_string(string $key, int $maxLength = 120): string
{
    $value = $_GET[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return mb_substr(trim($value), 0, $maxLength);
}

function format_date(?string $value): string
{
    if (!$value) {
        return '—';
    }
    $date = DateTimeImmutable::createFromFormat('Y-m-d', substr($value, 0, 10));
    return $date ? $date->format('d/m/Y') : '—';
}

function book_cover(array $book, string $size = 'M'): string
{
    $isbn = preg_replace('/[^0-9Xx]/', '', (string) ($book['isbn'] ?? ''));
    if ($isbn !== '') {
        return 'https://covers.openlibrary.org/b/isbn/' . rawurlencode($isbn) . '-' . $size . '.jpg?default=false';
    }
    return '';
}

function cover_fallback(array $book): string
{
    $tones = ['cover-forest', 'cover-ochre', 'cover-plum', 'cover-sage', 'cover-rust'];
    $index = abs((int) ($book['category_id'] ?? 0)) % count($tones);
    return $tones[$index];
}

function selected(string|int|null $current, string|int|null $candidate): string
{
    return (string) $current === (string) $candidate ? ' selected' : '';
}
