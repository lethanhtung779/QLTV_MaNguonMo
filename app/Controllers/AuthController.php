<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Core\Config;
use Library\Core\Session;
use Library\Repositories\ActivityRepository;
use Library\Repositories\UserRepository;
use Library\Services\AuthService;
use PDOException;
use RuntimeException;
use Throwable;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (current_user()) {
            redirect($this->homeForRole((string) current_user()['role']));
        }
        $this->render('auth/login', ['title' => 'Đăng nhập'], 'layouts/public');
    }

    public function login(): never
    {
        validate_csrf();
        try { $email = mb_strtolower(request_string('email', 190, true)); }
        catch (InvalidArgumentException $e) { $this->fail($e->getMessage(), '/login'); }
        $password = $_POST['password'] ?? '';
        if (!is_string($password)) {
            $this->fail('Vui lòng nhập email và mật khẩu hợp lệ.', '/login');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || strlen($password) > 4096) {
            $this->fail('Vui lòng nhập email và mật khẩu hợp lệ.', '/login');
        }

        try {
            $user = (new AuthService($this->db))->authenticate($email, $password);
            $this->success('Đăng nhập thành công.', $this->homeForRole((string) $user['role']));
        } catch (PDOException $exception) {
            error_log('Login database error: ' . $exception->getMessage());
            $this->fail('Không thể đăng nhập lúc này. Vui lòng thử lại sau.', '/login');
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/login');
        } catch (Throwable $exception) {
            error_log('Login error: ' . $exception->getMessage());
            $this->fail('Không thể đăng nhập lúc này. Vui lòng thử lại sau.', '/login');
        }
    }

    public function logout(): never
    {
        validate_csrf();
        Session::destroy();
        redirect('/');
    }

    public function registerForm(): void
    {
        if (!Config::bool('ALLOW_PUBLIC_REGISTRATION', true)) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Không tìm thấy trang'], 'layouts/public');
            return;
        }
        $this->render('auth/register', ['title' => 'Đăng ký độc giả'], 'layouts/public');
    }

    public function register(): never
    {
        validate_csrf();
        if (!Config::bool('ALLOW_PUBLIC_REGISTRATION', true)) {
            $this->fail('Thư viện hiện không mở đăng ký trực tuyến.', '/register');
        }

        try {
            $name = request_string('name', 160, true);
            $email = mb_strtolower(request_string('email', 190, true));
            $phone = request_string('phone', 32);
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirmation'] ?? '';
            if (!is_string($password) || !is_string($confirm)) {
                throw new InvalidArgumentException('Mật khẩu không hợp lệ.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Địa chỉ email không hợp lệ.');
            }
            if (!preg_match('/^[0-9+\s().-]{0,32}$/', $phone)) {
                throw new InvalidArgumentException('Số điện thoại không hợp lệ.');
            }
            if (!hash_equals($password, $confirm)) {
                throw new InvalidArgumentException('Mật khẩu xác nhận không khớp.');
            }
            (new AuthService($this->db))->registerReader($name, $email, $password, $phone);
            $this->success('Đăng ký thành công. Hãy đăng nhập để sử dụng thẻ thư viện.', '/login');
        } catch (PDOException $exception) {
            error_log('Reader registration database error: ' . $exception->getMessage());
            $message = $exception->getCode() === '23000'
                ? 'Email này đã được đăng ký.'
                : 'Không thể hoàn tất đăng ký lúc này. Vui lòng thử lại sau.';
            $this->fail($message, '/register');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/register');
        } catch (Throwable $exception) {
            error_log('Reader registration error: ' . $exception->getMessage());
            $message = $exception instanceof PDOException && $exception->getCode() === '23000'
                ? 'Email này đã được đăng ký.'
                : 'Không thể hoàn tất đăng ký lúc này. Vui lòng thử lại sau.';
            $this->fail($message, '/register');
        }
    }

    private function homeForRole(string $role): string
    {
        return $role === 'reader' ? '/account' : '/admin';
    }
}
