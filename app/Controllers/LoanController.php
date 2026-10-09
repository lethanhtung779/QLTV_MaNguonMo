<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Core\Config;
use Library\Repositories\LoanRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\SystemSettingRepository;
use Library\Services\LoanService;
use RuntimeException;
use Throwable;

final class LoanController extends Controller
{
    public function index(): void
    {
        require_auth(['admin', 'librarian']);
        (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))->expireReservations();
        $search = query_string('q');
        $status = is_string($_GET['status'] ?? null) &&
            in_array($_GET['status'], ['active', 'borrowed', 'overdue', 'returned'], true)
            ? $_GET['status'] : '';
        $repository = new LoanRepository($this->db);
        $loans = $repository->all($search, $status);
        $itemsByLoan = $repository->itemsForLoanIds(array_column($loans, 'id'));
        $this->render('admin/loans', [
            'title' => 'Mượn / Trả sách',
            'loans' => $loans,
            'itemsByLoan' => $itemsByLoan,
            'readers' => (new ReaderRepository($this->db))->all(),
            'copies' => $repository->availableCopies(),
            'reservations' => $repository->reservations(),
            'maxBooks' => (new SystemSettingRepository($this->db))->integer('max_books_per_reader', 5, 1, 50),
            'maxRenewals' => (new SystemSettingRepository($this->db))->integer('max_renewals', 2, 0, 10),
            'defaultLoanDays' => (new SystemSettingRepository($this->db))->integer('default_loan_days', 14, 1, 90),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $readerId = request_int('reader_id', 1);
            $rawCopyIds = $_POST['copy_ids'] ?? null;
            if (!is_array($rawCopyIds) || !$rawCopyIds) {
                throw new InvalidArgumentException('Chọn ít nhất một bản sao sách.');
            }
            $copyIds = [];
            foreach ($rawCopyIds as $rawCopyId) {
                if (!is_string($rawCopyId) || !ctype_digit($rawCopyId) || (int) $rawCopyId < 1) {
                    throw new InvalidArgumentException('Danh sách bản sao được chọn không hợp lệ.');
                }
                $copyIds[] = (int) $rawCopyId;
            }
            $borrowedAt = request_string('borrowed_at', 10, true);
            $dueAt = request_string('due_at', 10, true);
            $code = (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))->checkout(
                $readerId,
                $copyIds,
                (int) $user['id'],
                $borrowedAt,
                $dueAt,
                request_string('note', 500)
            );
            $this->success("Đã lập phiếu {$code}.", '/admin/loans');
        } catch (\PDOException $exception) {
            error_log('Loan checkout database error: ' . $exception->getMessage());
            $this->fail('Không thể lập phiếu mượn lúc này. Vui lòng thử lại sau.', '/admin/loans');
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/admin/loans');
        } catch (Throwable $exception) {
            error_log('Loan checkout error: ' . $exception->getMessage());
            $this->fail('Không thể lập phiếu mượn lúc này. Vui lòng thử lại sau.', '/admin/loans');
        }
    }

    public function returnBook(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                ->returnBook(
                    request_int('item_id', 1),
                    (int) $user['id'],
                    request_string('condition', 20) ?: 'good'
                );
            $this->success('Đã ghi nhận trả bản sao sách.', '/admin/loans');
        } catch (\PDOException $exception) {
            error_log('Loan return database error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận trả sách lúc này. Vui lòng thử lại sau.', '/admin/loans');
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/admin/loans');
        } catch (Throwable $exception) {
            error_log('Loan return error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận trả sách lúc này. Vui lòng thử lại sau.', '/admin/loans');
        }
    }

    public function renew(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $dueDate = (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                ->renew(request_int('item_id', 1), (int) $user['id']);
            $this->success('Đã gia hạn bản sách đến ' . date('d/m/Y', strtotime($dueDate)) . '.', '/admin/loans');
        } catch (\PDOException $exception) {
            error_log('Loan renewal database error: ' . $exception->getMessage());
            $this->fail('Không thể gia hạn sách lúc này. Vui lòng thử lại sau.', '/admin/loans');
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/admin/loans');
        } catch (Throwable $exception) {
            error_log('Loan renewal error: ' . $exception->getMessage());
            $this->fail('Không thể gia hạn sách lúc này. Vui lòng thử lại sau.', '/admin/loans');
        }
    }

    public function updateReservation(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        $id = request_int('id', 1);
        $status = request_string('status', 20, true);
        if (!in_array($status, ['ready', 'cancelled'], true)) {
            $this->fail('Trạng thái đặt trước không hợp lệ.', '/admin/loans');
        }
        try {
            (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                ->updateReservation($id, $status, (int) $user['id']);
        } catch (\PDOException $exception) {
            error_log('Reservation update database error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật yêu cầu đặt trước lúc này.', '/admin/loans');
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), '/admin/loans');
        } catch (Throwable $exception) {
            error_log('Reservation update error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật yêu cầu đặt trước lúc này.', '/admin/loans');
        }
        $this->success('Đã cập nhật yêu cầu đặt trước.', '/admin/loans');
    }
}
