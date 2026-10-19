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
        $dateFrom = query_string('date_from', 10);
        $dateTo = query_string('date_to', 10);

        $scope = trim((string) ($_GET['scope'] ?? 'operational'));
        $realOnly = ($scope !== 'archive');

        $repository = new LoanRepository($this->db);
        $kpis = $repository->operationalKpis($realOnly);

        $totalMatching = (int) $repository->all($search, $status, null, 0, true, $realOnly, $dateFrom, $dateTo)[0];
        $pagination = new \Library\Core\Pagination($totalMatching, 15);
        $loans = $repository->all($search, $status, $pagination->perPage, $pagination->offset, false, $realOnly, $dateFrom, $dateTo);
        $itemsByLoan = $repository->itemsForLoanIds(array_column($loans, 'id'));

        $reservations = $repository->reservations($realOnly);
        $kpis['pending_reservations'] = count($reservations);

        $realReaders = (new ReaderRepository($this->db))->all(realOnly: $realOnly);
        $availableCopies = $repository->availableCopies($realOnly);

        $this->render('admin/loans', [
            'title' => 'Quản lý Mượn / Trả',
            'loans' => $loans,
            'pagination' => $pagination,
            'itemsByLoan' => $itemsByLoan,
            'readers' => $realReaders,
            'copies' => $availableCopies,
            'reservations' => $reservations,
            'kpis' => $kpis,
            'maxBooks' => (new SystemSettingRepository($this->db))->integer('max_books_per_reader', 5, 1, 50),
            'maxRenewals' => (new SystemSettingRepository($this->db))->integer('max_renewals', 2, 0, 10),
            'defaultLoanDays' => (new SystemSettingRepository($this->db))->integer('default_loan_days', 14, 1, 90),
            'search' => $search,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'scope' => $scope,
            'realOnly' => $realOnly,
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
                request_string('note', 500),
                request_mutation_key()
            );
            $this->success("Đã lập phiếu {$code}.", $this->listPath());
        } catch (\PDOException $exception) {
            error_log('Loan checkout database error: ' . $exception->getMessage());
            $this->fail('Không thể lập phiếu mượn lúc này. Vui lòng thử lại sau.', $this->listPath());
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), $this->listPath());
        } catch (Throwable $exception) {
            error_log('Loan checkout error: ' . $exception->getMessage());
            $this->fail('Không thể lập phiếu mượn lúc này. Vui lòng thử lại sau.', $this->listPath());
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
            $this->success('Đã ghi nhận trả bản sao sách.', $this->listPath());
        } catch (\PDOException $exception) {
            error_log('Loan return database error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận trả sách lúc này. Vui lòng thử lại sau.', $this->listPath());
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), $this->listPath());
        } catch (Throwable $exception) {
            error_log('Loan return error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận trả sách lúc này. Vui lòng thử lại sau.', $this->listPath());
        }
    }

    public function returnAll(): never
    {
        $user=require_permission('loans.return');validate_csrf();
        try {
            (new LoanService($this->db,(float)Config::get('DAILY_FINE',5000)))->returnAll(request_int('loan_id',1),(int)$user['id'],request_string('condition',20)?:'good');
            $this->success('Đã nhận trả toàn bộ bản còn giữ của phiếu.',$this->listPath());
        } catch (Throwable $e) {
            if ($e instanceof RuntimeException && !$e instanceof \PDOException) $this->fail($e->getMessage(),$this->listPath());
            error_log('Return all error: '.$e->getMessage());$this->fail('Không thể nhận trả phiếu lúc này.',$this->listPath());
        }
    }
    public function renew(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $dueDate = (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                ->renew(request_int('item_id', 1), (int) $user['id'], null, request_mutation_key());
            $this->success('Đã gia hạn bản sách đến ' . date('d/m/Y', strtotime($dueDate)) . '.', $this->listPath());
        } catch (\PDOException $exception) {
            error_log('Loan renewal database error: ' . $exception->getMessage());
            $this->fail('Không thể gia hạn sách lúc này. Vui lòng thử lại sau.', $this->listPath());
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), $this->listPath());
        } catch (Throwable $exception) {
            error_log('Loan renewal error: ' . $exception->getMessage());
            $this->fail('Không thể gia hạn sách lúc này. Vui lòng thử lại sau.', $this->listPath());
        }
    }

    public function updateReservation(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        $id = request_int('id', 1);
        $status = request_string('status', 20, true);
        if (!in_array($status, ['ready', 'cancelled'], true)) {
            $this->fail('Trạng thái đặt trước không hợp lệ.', $this->listPath());
        }
        try {
            (new LoanService($this->db, (float) Config::get('DAILY_FINE', 5000)))
                ->updateReservation($id, $status, (int) $user['id']);
        } catch (\PDOException $exception) {
            error_log('Reservation update database error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật yêu cầu đặt trước lúc này.', $this->listPath());
        } catch (RuntimeException $exception) {
            $this->fail($exception->getMessage(), $this->listPath());
        } catch (Throwable $exception) {
            error_log('Reservation update error: ' . $exception->getMessage());
            $this->fail('Không thể cập nhật yêu cầu đặt trước lúc này.', $this->listPath());
        }
        $this->success('Đã cập nhật yêu cầu đặt trước.', $this->listPath());
    }

    /** Retain list context after a native form; destination is always this route. */
    private function listPath(): string
    {
        $query = [];
        $search = $_POST['_list_q'] ?? '';
        if (is_string($search) && trim($search) !== '') $query['q'] = mb_substr(trim($search), 0, 200);
        $status = $_POST['_list_status'] ?? '';
        if (is_string($status) && in_array($status, ['active','borrowed','overdue','returned'], true)) $query['status'] = $status;
        $page = $_POST['_list_page'] ?? '';
        if (is_string($page) && ctype_digit($page) && (int)$page > 1 && (int)$page <= 1000000) $query['page'] = (int)$page;
        return '/admin/loans' . ($query ? '?' . http_build_query($query) : '');
    }
}
