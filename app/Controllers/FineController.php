<?php
declare(strict_types=1);

namespace Library\Controllers;

use InvalidArgumentException;
use Library\Repositories\FineRepository;
use Library\Services\FineService;
use RuntimeException;
use Throwable;

final class FineController extends Controller
{
    public function index(): void
    {
        require_auth(['admin', 'librarian']);
        $service = new FineService($this->db);
        $service->synchronizeAll();
        $repository = new FineRepository($this->db);
        $readerValue = $_GET['reader'] ?? '';
        $readerId = is_string($readerValue) ? filter_var($readerValue, FILTER_VALIDATE_INT) : false;
        $readerId = $readerId !== false && $readerId > 0 ? $readerId : 0;

        $this->render('admin/fines', [
            'title' => 'Phí & thanh toán',
            'accounts' => $repository->accounts(),
            'readers' => $repository->readers(),
            'charges' => $readerId > 0 ? $repository->chargesForReader($readerId) : [],
            'payments' => $repository->payments(),
            'selectedReaderId' => $readerId,
        ]);
    }

    public function recordPayment(): never
    {
        $user = require_auth(['admin', 'librarian']);
        validate_csrf();
        try {
            $readerId = filter_var(request_string('reader_id', 20, true), FILTER_VALIDATE_INT);
            if ($readerId === false || $readerId < 1) {
                throw new InvalidArgumentException('Vui lòng chọn độc giả hợp lệ.');
            }
            (new FineService($this->db))->recordPayment(
                $readerId,
                request_string('amount', 20, true),
                request_string('method', 30, true),
                request_string('reference_code', 120),
                request_string('note', 300),
                (int) $user['id'],
                request_mutation_key()
            );
            $this->success('Đã ghi nhận và phân bổ khoản thanh toán.', '/admin/fines?reader=' . $readerId);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->fail($exception instanceof \PDOException ? 'Không thể xử lý giao dịch lúc này. Vui lòng thử lại sau.' : $exception->getMessage(), '/admin/fines');
        } catch (Throwable $exception) {
            error_log('Fine payment error: ' . $exception->getMessage());
            $this->fail('Không thể ghi nhận thanh toán lúc này. Vui lòng thử lại sau.', '/admin/fines');
        }
    }

    public function waive(): never
    {
        $user = require_auth(['admin']);
        validate_csrf();
        try {
            $chargeId = filter_var(request_string('charge_id', 20, true), FILTER_VALIDATE_INT);
            if ($chargeId === false || $chargeId < 1) {
                throw new InvalidArgumentException('Khoản phí cần điều chỉnh không hợp lệ.');
            }
            (new FineService($this->db))->waive(
                $chargeId,
                request_string('amount', 20, true),
                request_string('reason', 300, true),
                (int) $user['id']
            );
            $this->success('Đã ghi nhận khoản miễn/giảm phí.', '/admin/fines');
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $this->fail($exception instanceof \PDOException ? 'Không thể xử lý giao dịch lúc này. Vui lòng thử lại sau.' : $exception->getMessage(), '/admin/fines');
        } catch (Throwable $exception) {
            error_log('Fine waiver error: ' . $exception->getMessage());
            $this->fail('Không thể điều chỉnh khoản phí lúc này. Vui lòng thử lại sau.', '/admin/fines');
        }
    }
}
