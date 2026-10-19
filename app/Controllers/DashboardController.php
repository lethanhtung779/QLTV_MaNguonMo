<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\DashboardRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\FineRepository;
use Library\Repositories\RealStatisticsRepository;
use Library\Services\FineService;
use Library\Services\LoanReportCsvExporter;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class DashboardController extends Controller
{
    public function index(): void
    {
        require_auth(['admin', 'librarian']);
        $books = new BookRepository($this->db);
        $loans = new LoanRepository($this->db);
        $metrics = (new DashboardRepository($this->db))->metrics();
        $fineService = new FineService($this->db);
        $fineService->synchronizeAll();
        $fineBalances = (new FineRepository($this->db))->accounts();
        $this->render('admin/dashboard', [
            'title' => 'Tổng quan',
            'bookMetrics' => $books->metrics(),
            'readerMetrics' => $metrics,
            'loanMetrics' => $loans->summary(),
            'popularBooks' => $books->popular(5),
            'recentLoans' => $loans->recentOperational(7),
            'activities' => can('audit.view') ? (new ActivityRepository($this->db))->recent(8) : [],
            'categoryStats' => (new DashboardRepository($this->db))->categoryStats(),
            'reservations' => (new DashboardRepository($this->db))->reservationCount(),
            'monthly' => $loans->monthlyCounts(6),
            'outstandingFineBalance' => array_sum(array_map(
                static fn(array $account): float => (float) $account['balance'],
                $fineBalances
            )),
        ]);
    }

    public function statistics(): void
    {
        require_auth(['admin', 'librarian']);
        (new FineService($this->db))->synchronizeAll();
        try {
            [$dateFrom, $dateTo, $status] = $this->reportFilters();
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage(), '/admin/statistics');
        }
        $loans = new LoanRepository($this->db);
        $real = new RealStatisticsRepository($this->db);
        $reports = new \Library\Repositories\ReportRepository($this->db);
        $pagination = new \Library\Core\Pagination($loans->reportRows($dateFrom,$dateTo,$status,1,0,true,true)[0],20);
        $inventoryPagination = new \Library\Core\Pagination($reports->rows('inventory',1,0,true,null,null,true)[0],20,'inventory_page');
        $finePagination = new \Library\Core\Pagination($reports->rows('fines',1,0,true,$dateFrom,$dateTo,true)[0],20,'fine_page');
        $this->render('admin/statistics', [
            'title' => 'Thống kê',
            'bookMetrics' => $real->bookMetrics(),
            'readerMetrics' => $real->readerMetrics(),
            'loanMetrics' => $real->loanMetrics(),
            'categoryStats' => $real->categoryStats(),
            'monthly' => $real->filteredMonthly($dateFrom,$dateTo),
            'popularBooks' => $real->popularBooks(8),
            'inventoryPagination' => $inventoryPagination,
            'finePagination' => $finePagination,
            'collected' => $real->collected($dateFrom,$dateTo),
            'outstanding' => $real->outstanding(),
            'inventoryReport' => $reports->rows('inventory',$inventoryPagination->perPage,$inventoryPagination->offset,false,null,null,true),
            'fineReport' => $reports->rows('fines',$finePagination->perPage,$finePagination->offset,false,$dateFrom,$dateTo,true),
            'pagination' => $pagination,
            'reportRows' => $loans->reportRows($dateFrom,$dateTo,$status,$pagination->perPage,$pagination->offset,false,true),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'reportStatus' => $status,
        ]);
    }

    public function exportCsv(): never
    {
        $user = require_auth(['admin', 'librarian']);
        try {
            (new FineService($this->db))->synchronizeAll();
            $kind = query_string('kind');
            if (in_array($kind, ['inventory', 'fines', 'books', 'readers', 'monthly'], true)) $this->exportAdditionalCsv($kind, (int) $user['id']);
            [$dateFrom, $dateTo, $status] = $this->reportFilters();
            $rows = (new LoanRepository($this->db))->reportRows($dateFrom, $dateTo, $status, 10001, 0, false, true);
            if (count($rows) > 10000) {
                throw new InvalidArgumentException('Báo cáo vượt quá 10.000 dòng. Hãy thu hẹp khoảng thời gian hoặc chọn trạng thái.');
            }
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new RuntimeException('Không thể mở luồng xuất báo cáo.');
            }
            (new ActivityRepository($this->db))->log(
                (int) $user['id'],
                'loan_report_exported',
                'report',
                null,
                "Xuất báo cáo mượn sách {$dateFrom} đến {$dateTo}"
            );
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="paper-page-loans-' . str_replace('-', '', $dateFrom) . '-' . str_replace('-', '', $dateTo) . '.csv"');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            LoanReportCsvExporter::write($output, $rows);
            fclose($output);
            exit;
        } catch (InvalidArgumentException $exception) {
            $this->fail($exception->getMessage(), '/admin/statistics');
        } catch (Throwable $exception) {
            error_log('Loan report export error: ' . $exception->getMessage());
            $this->fail('Không thể xuất báo cáo lúc này. Vui lòng thử lại sau.', '/admin/statistics');
        }
    }

    private function exportAdditionalCsv(string $kind, int $actor): never
    {
        if ($kind==='books') {
            require_permission('books.view');
            $rows=$this->db->query('SELECT b.book_code,b.title,b.author,b.isbn,c.name AS category,b.publisher,b.publication_year,b.is_active,COUNT(cp.id) AS copies,SUM(cp.status="available") AS available FROM books b LEFT JOIN categories c ON c.id=b.category_id LEFT JOIN book_copies cp ON cp.book_id=b.id WHERE b.book_code NOT LIKE \'DEMO-%\' GROUP BY b.id,c.id ORDER BY b.title LIMIT 10001')->fetchAll();
            $headers=['Mã sách','Tên sách','Tác giả','ISBN','Thể loại','Nhà xuất bản','Năm','Lưu hành','Bản sao','Khả dụng'];
        } elseif ($kind==='readers') {
            require_permission('readers.view');
            $rows=$this->db->query('SELECT card_number,full_name,email,phone,address,is_active,expires_on,created_at FROM readers r WHERE r.card_number NOT LIKE \'DEMO-%\' ORDER BY full_name LIMIT 10001')->fetchAll();
            $headers=['Mã thẻ','Họ tên','Email','Điện thoại','Địa chỉ','Hoạt động','Hết hạn','Đăng ký'];
        } elseif ($kind==='monthly') {
            [$from,$to]=$this->reportFilters();
            $rows=(new RealStatisticsRepository($this->db))->monthlySummary($from,$to);
            $headers=['Tháng mượn','Bản đã mượn','Đã trả trong nhóm mượn','Còn quá hạn trong nhóm mượn'];
        } else {
            [$from,$to]=$this->reportFilters();
            $rows = (new \Library\Repositories\ReportRepository($this->db))->rows($kind,10001,0,false,$from,$to,true);
            $headers=\Library\Repositories\ReportRepository::headers($kind);
        }
        if (count($rows)>10000) throw new InvalidArgumentException('Báo cáo vượt 10.000 dòng.');
        (new ActivityRepository($this->db))->log($actor,'report_exported','report',null,'Xuất báo cáo '.$kind);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="paper-page-'.$kind.'.csv"');
        header('Cache-Control: no-store');
        $out=fopen('php://output','wb');
        if (!$out) throw new RuntimeException('Không thể xuất báo cáo.');
        fwrite($out,"\xEF\xBB\xBF");
        fputcsv($out,$headers,',','"','',"\r\n");
        foreach ($rows as $row) fputcsv($out,array_map(LoanReportCsvExporter::safeCell(...),array_values($row)),',','"','',"\r\n");
        fclose($out); exit;
    }

    private function reportFilters(): array
    {
        $today = new DateTimeImmutable('today');
        $fromRaw = $_GET['from'] ?? '';
        $toRaw = $_GET['to'] ?? '';
        $fromRaw = is_string($fromRaw) && $fromRaw !== '' ? $fromRaw : $today->modify('-90 days')->format('Y-m-d');
        $toRaw = is_string($toRaw) && $toRaw !== '' ? $toRaw : $today->format('Y-m-d');
        $dateFrom = DateTimeImmutable::createFromFormat('!Y-m-d', $fromRaw);
        $dateTo = DateTimeImmutable::createFromFormat('!Y-m-d', $toRaw);
        if (!$dateFrom || !$dateTo || $dateFrom->format('Y-m-d') !== $fromRaw || $dateTo->format('Y-m-d') !== $toRaw ||
            $dateFrom > $dateTo || $dateTo > $today || $dateFrom->diff($dateTo)->days > 366) {
            throw new InvalidArgumentException('Khoảng ngày báo cáo không hợp lệ; chọn tối đa 366 ngày đến hôm nay.');
        }
        $statusRaw = $_GET['status'] ?? '';
        $status = is_string($statusRaw) && in_array($statusRaw, ['borrowed', 'overdue', 'returned'], true)
            ? $statusRaw : '';
        return [$fromRaw, $toRaw, $status];
    }
}
