<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\FineRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\ReservationRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Repositories\UserRepository;
use Library\Services\LoanService;
use Library\Services\FineService;
use Library\Core\Session;
use RuntimeException;
use PDOException;
use Throwable;

final class ReaderController extends Controller
{
    public function dashboard(): void
    {
        $user = $this->activeReader(require_auth(['reader']));
        $readerId = (int) $user['reader_id'];
        (new LoanService($this->db, 5000))->expireReservations();
        (new \Library\Services\ReminderService($this->db))->synchronize((int)$user['id']);
        $fineService = new FineService($this->db);
        $fineBalance = $fineService->balance($readerId);
        $this->render('reader/dashboard', [
            'title' => 'Tài khoản thư viện',
            'user' => $user,
            'reader' => (new ReaderRepository($this->db))->find($readerId),
            'loans' => (new LoanRepository($this->db))->forReader($readerId),
            'reservations' => (new ReservationRepository($this->db))->forReader($readerId),
            'fines' => $fineBalance,
            'fineCharges' => (new FineRepository($this->db))->chargesForReader($readerId),
            'finePayments' => (new FineRepository($this->db))->paymentsForReader($readerId),
            'books' => (new BookRepository($this->db))->search(),
            'maxRenewals' => (new SystemSettingRepository($this->db))->integer('max_renewals', 2, 0, 10),
        ]);
    }

    public function profile(): void
    {
        $user = $this->activeReader(require_auth(['reader']));
        $this->render('reader/profile', [
            'title' => 'Hồ sơ độc giả',
            'user' => $user,
            'reader' => (new ReaderRepository($this->db))->find((int) $user['reader_id']),
        ]);
    }

    public function updateProfile(): never
    {
        $user = $this->activeReader(require_auth(['reader']));
        validate_csrf();
        try {
            $name = request_string('name', 160, true);
            $email = mb_strtolower(request_string('email', 190, true));
            $phone = request_string('phone', 32);
            $address = request_string('address', 400);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
                ($phone !== '' && !preg_match('/^[0-9+\s().-]{7,32}$/', $phone))) {
                throw new InvalidArgumentException('Email hoặc số điện thoại không hợp lệ.');
            }

            $users = new UserRepository($this->db);
            $existing = $users->findByEmail($email);
            if ($existing && (int) $existing['id'] !== (int) $user['id']) {
                throw new InvalidArgumentException('Email này đã được tài khoản khác sử dụng.');
            }

            $this->db->beginTransaction();
            $users->updateProfile((int) $user['id'], $name, $email);
            $reader = (new ReaderRepository($this->db))->findForUpdate((int) $user['reader_id']);
            if (!$reader) {
                throw new RuntimeException('Thẻ độc giả không còn hoạt động.');
            }
            $data = [
                'user_id' => (int) $user['id'],
                'card_number' => $reader['card_number'],
                'full_name' => $name,
                'phone' => $phone ?: null,
                'email' => $email,
                'address' => $address ?: null,
                'expires_on' => $reader['expires_on'],
            ];
            (new ReaderRepository($this->db))->update((int) $reader['id'], $data);
            $this->db->commit();
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;
            $this->success('Đã cập nhật hồ sơ.', '/account/profile');
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reader profile update database error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật hồ sơ lúc này. Vui lòng thử lại sau.', '/account/profile');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->fail($exception->getMessage(), '/account/profile');
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Reader profile update error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật hồ sơ lúc này. Vui lòng thử lại sau.', '/account/profile');
        }
    }

    public function changePassword(): never
    {
        $user = $this->activeReader(require_auth(['reader']));
        validate_csrf();
        $repository = new UserRepository($this->db);
        $record = $repository->findById((int) $user['id']);
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        if (!is_string($current) || !is_string($new) || !is_string($confirmation)) {
            $this->fail('Thông tin mật khẩu không hợp lệ.', '/account/profile');
        }
        if (!$record || !password_verify($current, $record['password_hash'])) {
            $this->fail('Mật khẩu hiện tại không chính xác.', '/account/profile');
        }
        if (strlen($new) > 72 || strlen($new) < 12 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) ||
            !preg_match('/[0-9]/', $new) || !hash_equals($new, $confirmation)) {
            $this->fail('Mật khẩu mới cần tối thiểu 12 ký tự, có chữ hoa, chữ thường, số và xác nhận trùng khớp.', '/account/profile');
        }
        $repository->updatePassword((int) $user['id'], password_hash($new, PASSWORD_DEFAULT));
        (new ActivityRepository($this->db))->log((int) $user['id'], 'password_changed', 'user', (int) $user['id'], 'Đổi mật khẩu tài khoản');
        Session::regenerate();
        $this->success('Đã đổi mật khẩu.', '/account/profile');
    }

    public function reserve(): never
    {
        $user = $this->activeReader(require_auth(['reader']));
        validate_csrf();
        try {
            (new LoanService($this->db, 5000))->expireReservations();
            (new LoanService($this->db, 5000))->reserve(
                (int) $user['reader_id'],
                request_int('book_id', 1),
                (int) $user['id']
            );
            $this->success('Đã ghi nhận yêu cầu đặt trước. Thư viện sẽ thông báo khi sách sẵn sàng.', '/account');
        } catch (PDOException $exception) {
            error_log('Reader reservation database error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận yêu cầu lúc này. Vui lòng thử lại sau.', '/account');
        } catch (RuntimeException $exception) {
            $this->fail($exception instanceof \PDOException ? 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.' : $exception->getMessage(), '/account');
        } catch (Throwable $exception) {
            error_log('Reader reservation error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận yêu cầu lúc này. Vui lòng thử lại sau.', '/account');
        }
    }

    public function cancelReservation(): never
    {
        $user = $this->activeReader(require_auth(['reader']));
        validate_csrf();
        try {
            (new LoanService($this->db, 5000))->cancelReaderReservation(
                request_int('id', 1),
                (int) $user['reader_id']
            );
        } catch (RuntimeException $exception) {
            $this->fail($exception instanceof \PDOException ? 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.' : $exception->getMessage(), '/account');
        }
        $this->success('Đã hủy yêu cầu đặt trước.', '/account');
    }

    public function renewLoan(): never
    {
        $user = $this->activeReader(require_auth(['reader']));
        validate_csrf();
        try {
            $dueDate = (new LoanService($this->db, 5000))->renew(
                request_int('item_id', 1),
                (int) $user['id'],
                (int) $user['reader_id']
            );
            $this->success('Đã gia hạn sách đến ' . date('d/m/Y', strtotime($dueDate)) . '.', '/account');
        } catch (RuntimeException $exception) {
            $this->fail($exception instanceof \PDOException ? 'Không thể xử lý yêu cầu lúc này. Vui lòng thử lại sau.' : $exception->getMessage(), '/account');
        } catch (Throwable $exception) {
            error_log('Reader loan renewal error: ' . $exception->getMessage());
            $this->fail('Không thể gia hạn sách lúc này. Vui lòng thử lại sau.', '/account');
        }
    }

    private function activeReader(array $user): array
    {
        $reader = (new ReaderRepository($this->db))->findByUser((int) $user['id']);
        if (!$reader) {
            unset($_SESSION['user']);
            Session::regenerate();
            flash('error', 'Thẻ độc giả của bạn không còn hoạt động. Vui lòng liên hệ thủ thư.');
            redirect('/login');
        }
        $user['reader_id'] = (int) $reader['id'];
        $_SESSION['user']['reader_id'] = (int) $reader['id'];
        return $user;
    }
}
