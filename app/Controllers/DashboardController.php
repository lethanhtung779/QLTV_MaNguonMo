<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\DashboardRepository;
use Library\Repositories\LoanRepository;
use Library\Repositories\FineRepository;
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
            'recentLoans' => $loans->recent(7),
            'activities' => (new ActivityRepository($this->db))->recent(8),
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
        $this->render('admin/statistics', [
            'title' => 'Thống kê',
            'bookMetrics' => (new BookRepository($this->db))->metrics(),
            'readerMetrics' => (new DashboardRepository($this->db))->metrics(),
            'loanMetrics' => $loans->summary(),
            'categoryStats' => (new DashboardRepository($this->db))->categoryStats(),
            'monthly' => $loans->monthlyCounts(6),
            'popularBooks' => (new BookRepository($this->db))->popular(8),
            'inventoryReport' => (new \Library\Repositories\ReportRepository($this->db))->rows('inventory', 50),
            'fineReport' => (new \Library\Repositories\ReportRepository($this->db))->rows('fines', 50),
            'reportRows' => $loans->reportRows($dateFrom, $dateTo, $status, 50),
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
            if (in_array($kind, ['inventory', 'fines'], true)) $this->exportAdditionalCsv($kind, (int) $user['id']);
            [$dateFrom, $dateTo, $status] = $this->reportFilters();
            $rows = (new LoanRepository($this->db))->reportRows($dateFrom, $dateTo, $status, 10001);
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
        $rows = (new \Library\Repositories\ReportRepository($this->db))->rows($kind);
        if (count($rows)>10000) throw new InvalidArgumentException('Báo cáo vượt 10.000 dòng.');
        (new ActivityRepository($this->db))->log($actor,'report_exported','report',null,'Xuất báo cáo '.$kind);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="paper-page-'.$kind.'.csv"');
        header('Cache-Control: no-store');
        $out=fopen('php://output','wb');
        if (!$out) throw new RuntimeException('Không thể xuất báo cáo.');
        fwrite($out,"\xEF\xBB\xBF");
        fputcsv($out,\Library\Repositories\ReportRepository::headers($kind),',','"','',"\r\n");
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
