<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Core\Config;
use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\CategoryRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Services\LoanService;
use PDOException;
use Throwable;

final class ManagementController extends Controller
{
    public function books(): void
    {
        require_auth(['admin', 'librarian']);
        $repository = new BookRepository($this->db);
        $search = query_string('q');
        $categoryRaw = $_GET['category'] ?? 0;
        $categoryId = is_string($categoryRaw) || is_int($categoryRaw)
            ? filter_var($categoryRaw, FILTER_VALIDATE_INT) : false;
        $categoryId = $categoryId === false ? 0 : max(0, min(1000000, $categoryId));
        $availability = is_string($_GET['availability'] ?? null) &&
            in_array($_GET['availability'], ['available', 'unavailable'], true)
            ? $_GET['availability'] : '';
        $pagination = new \Library\Core\Pagination($repository->countSearch($search, $categoryId, $availability));
        $sort = query_string('sort');
        $this->render('admin/books', [
            'title' => 'Quản lý sách',
            'books' => $repository->search($search, $categoryId, $availability, $pagination->perPage, $pagination->offset, $sort),
            'pagination' => $pagination,
            'sort' => $sort,
            'categories' => $repository->allCategories(),
            'search' => $search,
            'categoryId' => $categoryId,
            'availability' => $availability,
        ]);
    }

    public function settings(): void
    {
        require_auth(['admin']);
        $settings = new SystemSettingRepository($this->db);
        $this->render('admin/settings', [
            'title' => 'Cài đặt',
            'environment' => (string) Config::get('APP_ENV', 'production'),
            'debug' => Config::bool('APP_DEBUG', false),
            'timezone' => (string) Config::get('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
            'registration' => Config::bool('ALLOW_PUBLIC_REGISTRATION', true),
            'dailyFine' => $settings->decimal('fine_per_overdue_day', (float) Config::get('DAILY_FINE', 5000), 0, 10000000),
            'damagedCopyFine' => $settings->decimal('damaged_copy_fine', 50000, 0, 99999999),
            'lostCopyFine' => $settings->decimal('lost_copy_fine', 200000, 0, 99999999),
            'libraryName' => $settings->string('library_name', 'Paper & Page'),
            'maxBooks' => $settings->integer('max_books_per_reader', 5, 1, 50),
            'loanDays' => $settings->integer('default_loan_days', 14, 1, 90),
            'maxRenewals' => $settings->integer('max_renewals', 2, 0, 10),
            'renewalDays' => $settings->integer('renewal_days', 14, 1, 90),
            'reservationExpiryDays' => $settings->integer('reservation_expiry_days', 3, 1, 30),
            'policyVersion' => $settings->currentPolicyVersion(),
            'databaseName' => (string) Config::get('DB_NAME', ''),
            'phpVersion' => PHP_VERSION,
            'pdoDrivers' => \PDO::getAvailableDrivers(),
        ]);
    }

    public function saveSettings(): never
    {
        $user = require_auth(['admin']);
        validate_csrf();
        try {
            $values = [];
            foreach ([
                'library_name',
                'max_books_per_reader',
                'default_loan_days',
                'max_renewals',
                'renewal_days',
                'fine_per_overdue_day',
                'reservation_expiry_days',
                'damaged_copy_fine',
                'lost_copy_fine',
            ] as $key) {
                $values[$key] = request_string($key, 120, true);
            }
            $this->db->beginTransaction();
            (new SystemSettingRepository($this->db))->update($values, (int) $user['id']);
            $this->db->commit();
            $this->success('Đã lưu chính sách thư viện.', '/admin/settings');
        } catch (InvalidArgumentException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->fail($exception->getMessage(), '/admin/settings');
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Library settings update error: ' . $exception->getMessage());
            $this->fail('Không thể lưu cấu hình lúc này. Vui lòng thử lại sau.', '/admin/settings');
        }
    }

    public function saveBook(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        $path = $this->postPath('return_to', '/admin/books');
        try {
            $id = request_int('id', 0);
            $totalCopies = request_int('total_copies', 1, 1000000);
            $isbn = preg_replace('/[^0-9Xx]/', '', request_string('isbn', 20));
            $categoryId = request_int('category_id', 0, 1000000);
            $yearRaw = request_string('publication_year', 4);
            $year = $yearRaw === '' ? null : filter_var($yearRaw, FILTER_VALIDATE_INT);
            if ($year !== null && ($year === false || $year < 1 || $year > (int) date('Y') + 1)) {
                throw new InvalidArgumentException('Năm xuất bản không hợp lệ.');
            }

            $data = [
                'book_code' => strtoupper(request_string('book_code', 40, true)),
                'title' => request_string('title', 240, true),
                'author' => request_string('author', 190, true),
                'isbn' => $isbn ?: null,
                'category_id' => $categoryId ?: null,
                'publisher' => request_string('publisher', 190) ?: null,
                'publication_year' => $year,
                'description' => request_string('description', 5000) ?: null,
                'total_copies' => $totalCopies,
                'cover_url' => null,
            ];
            if ($id > 0) {
                $repository = new BookRepository($this->db);
                $this->db->beginTransaction();
                $old = $repository->findForUpdate($id);
                if (!$old || $totalCopies < $repository->countHeldCopies($id)) {
                    $this->db->rollBack();
                    throw new InvalidArgumentException('Không thể giảm số bản thấp hơn số sách đang cho mượn hoặc đã giữ chỗ.');
                }
                $repository->update($id, $data);
                (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                    ->allocateAvailableReservations($id, (int) $user['id']);
                (new ActivityRepository($this->db))->log((int) $user['id'], 'book_updated', 'book', $id, 'Cập nhật ' . $data['title']);
                $this->db->commit();
            } else {
                $this->db->beginTransaction();
                $id = (new BookRepository($this->db))->create($data);
                (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                    ->allocateAvailableReservations($id, (int) $user['id']);
                (new ActivityRepository($this->db))->log((int) $user['id'], 'book_created', 'book', $id, 'Thêm ' . $data['title']);
                $this->db->commit();
            }
            $this->success('Đã lưu thông tin sách.', $path);
        } catch (InvalidArgumentException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->fail($exception->getMessage(), $path);
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $message = $exception->getCode() === '23000'
                ? 'Mã sách hoặc ISBN đã tồn tại.'
                : 'Không thể lưu sách. Hãy kiểm tra dữ liệu thể loại và thử lại.';
            $this->fail($message, $path);
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Book save error: ' . $exception->getMessage());
            $this->fail('Không thể lưu sách lúc này. Vui lòng thử lại sau.', $path);
        }
    }

    public function deleteBook(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        $id = request_int('id', 1);
        $path = $this->postPath('return_to', '/admin/books');
        $repository = new BookRepository($this->db);
        $this->db->beginTransaction();
        try {
            $book = $repository->findForUpdate($id);
            if (!$book || !$book['is_active']) throw new InvalidArgumentException('Không tìm thấy sách.');
            if ($repository->deleteConflicts($id)) throw new InvalidArgumentException('Sách còn bản đang mượn hoặc yêu cầu đặt trước; hãy xử lý trước khi ngừng lưu hành.');
            $repository->delete($id);
            (new ActivityRepository($this->db))->log((int)$user['id'],'book_archived','book',$id,'Ngừng lưu hành '.$book['title']);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($exception instanceof InvalidArgumentException ? $exception->getMessage() : 'Không thể ngừng lưu hành sách lúc này.', $path);
        }
        $this->success('Đã ngừng lưu hành sách.', $path);
    }

    public function categories(): void
    {
        require_auth(['admin', 'librarian']);
        $this->render('admin/categories', [
            'title' => 'Thể loại',
            'categories' => (new CategoryRepository($this->db))->all(),
        ]);
    }

    public function saveCategory(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $id = request_int('id', 0);
            $name = request_string('name', 120, true);
            $description = request_string('description', 500);
            $repository = new CategoryRepository($this->db);
            if ($id > 0) {
                $repository->update($id, $name, $description);
                (new ActivityRepository($this->db))->log((int) $user['id'], 'category_updated', 'category', $id, 'Cập nhật thể loại ' . $name);
            } else {
                $repository->create($name, $description);
                (new ActivityRepository($this->db))->log((int) $user['id'], 'category_created', 'category', null, 'Thêm thể loại ' . $name);
            }
            $this->success('Đã lưu thể loại.', '/admin/categories');
        } catch (Throwable $exception) {
            $this->fail($exception instanceof InvalidArgumentException
                ? $exception->getMessage() : 'Không thể lưu thể loại. Tên thể loại có thể đã tồn tại.', '/admin/categories');
        }
    }

    public function deleteCategory(): never
    {
        require_auth(['admin', 'librarian']);
        validate_csrf();
        $id = request_int('id', 1);
        if (!(new CategoryRepository($this->db))->delete($id)) {
            $this->fail('Không tìm thấy thể loại hoặc vẫn còn sách đang sử dụng thể loại này.', '/admin/categories');
        }
        $this->success('Đã xóa thể loại.', '/admin/categories');
    }

    public function readers(): void
    {
        require_auth(['admin', 'librarian']);
        $search = query_string('q');
        $repository = new ReaderRepository($this->db);
        $inactive = query_string('state') === 'inactive';
        $pagination = new \Library\Core\Pagination($repository->countSearch($search, $inactive), 20);
        $this->render('admin/readers', [
            'title' => 'Độc giả',
            'readers' => $repository->all($search, $pagination->perPage, $pagination->offset, $inactive),
            'pagination' => $pagination,
            'inactive' => $inactive,
            'search' => $search,
        ]);
    }

    public function saveReader(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $id = request_int('id', 0);
            $card = strtoupper(request_string('card_number', 40));
            if ($card === '') {
                $card = 'DG' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            }
            $email = mb_strtolower(request_string('email', 190));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email độc giả không hợp lệ.');
            }
            $phone = request_string('phone', 32);
            if ($phone !== '' && !preg_match('/^[0-9+\s().-]{7,32}$/', $phone)) {
                throw new InvalidArgumentException('Số điện thoại không hợp lệ.');
            }
            $data = [
                'user_id' => null,
                'card_number' => $card,
                'full_name' => request_string('full_name', 160, true),
                'phone' => $phone ?: null,
                'email' => $email ?: null,
                'address' => request_string('address', 400) ?: null,
                'expires_on' => request_string('expires_on', 10) ?: null,
            ];
            if ($data['expires_on'] !== null) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $data['expires_on']);
                if (!$date || $date->format('Y-m-d') !== $data['expires_on']) {
                    throw new InvalidArgumentException('Ngày hết hạn thẻ không hợp lệ.');
                }
            }
            $repository = new ReaderRepository($this->db);
            $active = isset($_POST['is_active']) ? request_int('is_active',0,1) : 1;
            $this->db->beginTransaction();
            if ($id > 0) {
                $lock=$this->db->prepare('SELECT id FROM readers WHERE id=? FOR UPDATE'); $lock->execute([$id]);
                if (!$lock->fetchColumn()) throw new InvalidArgumentException('Không tìm thấy độc giả.');
                if (!$active) $this->validateReaderDeactivation($id);
                $repository->update($id, $data);
                $this->db->prepare('UPDATE readers SET is_active=? WHERE id=?')->execute([$active,$id]);
                (new ActivityRepository($this->db))->log((int)$user['id'],'reader_updated','reader',$id,'Cập nhật độc giả '.$data['full_name']);
            } else {
                $id=$repository->create($data);
                $this->db->prepare('UPDATE readers SET is_active=? WHERE id=?')->execute([$active,$id]);
                (new ActivityRepository($this->db))->log((int)$user['id'],'reader_created','reader',$id,'Thêm độc giả '.$data['full_name']);
            }
            $this->db->commit();
            $this->success('Đã lưu thông tin độc giả.', '/admin/readers');
        } catch (InvalidArgumentException $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($exception->getMessage(), '/admin/readers');
        } catch (PDOException $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail($exception->getCode() === '23000' ? 'Mã thẻ đã tồn tại.' : 'Không thể lưu hồ sơ độc giả.', '/admin/readers');
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->fail('Không thể lưu hồ sơ độc giả lúc này.', '/admin/readers');
        }
    }

    private function validateReaderDeactivation(int $id): void
    {
        if ((new ReaderRepository($this->db))->hasActiveLoans($id)) throw new InvalidArgumentException('Độc giả còn sách chưa trả.');
        $s=$this->db->prepare('SELECT 1 FROM reservations WHERE reader_id=? AND status IN ("pending","ready") LIMIT 1'); $s->execute([$id]);
        if ($s->fetchColumn()) throw new InvalidArgumentException('Hãy hủy hoặc hoàn tất yêu cầu đặt trước của độc giả trước.');
        $charges=(new \Library\Repositories\FineRepository($this->db))->chargesForReader($id);
        if (array_sum(array_column($charges,'balance'))>0) throw new InvalidArgumentException('Độc giả còn dư nợ phí; hãy xử lý trước khi ngừng thẻ.');
    }
    public function deleteReader(): never
    {
        $user=require_auth(['admin','librarian']); validate_csrf();
        try {
            $id=request_int('id',1); $this->db->beginTransaction();
            $reader=(new ReaderRepository($this->db))->findForUpdate($id);
            if (!$reader) throw new InvalidArgumentException('Không tìm thấy độc giả.');
            $this->validateReaderDeactivation($id);
            (new ReaderRepository($this->db))->deactivate($id);
            (new ActivityRepository($this->db))->log((int)$user['id'],'reader_deactivated','reader',$id,'Ngừng hoạt động thẻ '.$reader['card_number']);
            $this->db->commit(); $this->success('Đã ngừng hoạt động thẻ độc giả.','/admin/readers');
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); $this->fail($e instanceof InvalidArgumentException ? $e->getMessage() : 'Không thể cập nhật thẻ lúc này.','/admin/readers'); }
    }
}
