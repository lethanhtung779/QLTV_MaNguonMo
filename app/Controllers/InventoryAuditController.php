<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\CopyRepository;
use Library\Repositories\InventoryAuditRepository;
use Library\Services\BarcodeService;
use RuntimeException;
use Throwable;

final class InventoryAuditController extends Controller
{
    public function index(): void
    {
        require_permission('books.view');
        $repo = new InventoryAuditRepository($this->db);
        $audits = $repo->listAudits();

        $shelves = $this->db->query("
            SELECT shelf_location, COUNT(*) AS count
            FROM book_copies
            WHERE shelf_location IS NOT NULL AND shelf_location != ''
            GROUP BY shelf_location
            ORDER BY shelf_location
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $categories = $this->db->query("
            SELECT c.id, c.name, COUNT(cp.id) AS count
            FROM categories c
            JOIN books b ON b.category_id = c.id
            JOIN book_copies cp ON cp.book_id = b.id
            WHERE b.book_code NOT LIKE 'DEMO-%'
            GROUP BY c.id, c.name
            ORDER BY c.name
        ")->fetchAll(\PDO::FETCH_ASSOC);

        $this->render('admin/inventory_audits', [
            'title' => 'Kiểm kê thư viện',
            'audits' => $audits,
            'shelves' => $shelves,
            'categories' => $categories,
        ], 'layouts/app');
    }

    public function create(): void
    {
        validate_csrf();
        $user = require_permission('books.create');

        $title = trim((string) ($_POST['title'] ?? ''));
        $scope = trim((string) ($_POST['scope'] ?? 'all'));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($title === '') {
            $this->fail('Vui lòng nhập tên đợt kiểm kê.', '/admin/inventory/audits');
        }

        try {
            $repo = new InventoryAuditRepository($this->db);
            $audit = $repo->createAudit($title, $scope, (int) $user['id'], $notes ?: null);

            (new ActivityRepository($this->db))->log(
                (int) $user['id'],
                'inventory_audit_created',
                'inventory_audit',
                (int) $audit['id'],
                'Tạo đợt kiểm kê: ' . $audit['audit_code'] . ' - ' . $title
            );

            $this->success('Đã tạo đợt kiểm kê mới thành công.', '/admin/inventory/audits/' . (int) $audit['id']);
        } catch (Throwable $e) {
            $this->fail('Lỗi tạo đợt kiểm kê: ' . $e->getMessage(), '/admin/inventory/audits');
        }
    }

    public function detail(int $id): void
    {
        require_permission('books.view');
        $repo = new InventoryAuditRepository($this->db);
        $audit = $repo->findAudit($id);
        if (!$audit) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Không tìm thấy đợt kiểm kê'], 'layouts/app');
            return;
        }

        $items = $repo->auditItems($id);
        $missing = $repo->missingCopies($id);

        $this->render('admin/inventory_audit_detail', [
            'title' => 'Chi tiết kiểm kê: ' . $audit['audit_code'],
            'audit' => $audit,
            'items' => $items,
            'missing' => $missing,
        ], 'layouts/app');
    }

    public function scan(int $id): void
    {
        validate_csrf();
        $user = require_permission('books.update');

        $code = trim((string) ($_POST['code'] ?? ''));
        $condition = trim((string) ($_POST['condition'] ?? 'good'));
        $location = trim((string) ($_POST['location'] ?? ''));

        try {
            $repo = new InventoryAuditRepository($this->db);
            $result = $repo->scanCopy($id, $code, (int) $user['id'], $condition, $location ?: null);

            $msg = 'Đã ghi nhận quét bản sao: ' . $result['copy']['copy_code'] . ' (' . $result['copy']['book_title'] . ').';
            if (!empty($result['already_scanned'])) {
                $msg = 'Bản sao ' . $result['copy']['copy_code'] . ' đã được quét trước đó; hệ thống đã cập nhật tình trạng mới nhất.';
            }
            if (!empty($result['out_of_scope'])) {
                $msg .= ' [CẢNH BÁO: Bản sao này nằm ngoài phạm vi đăng ký của đợt kiểm kê!]';
            }
            if (!$result['is_matched']) {
                $msg .= ' [LƯU Ý: Vị trí thực tế khác vị trí kệ hệ thống]';
            }
            if (($result['copy']['status'] ?? '') === 'on_loan') {
                $msg .= ' [CẢNH BÁO: Bản sao này hệ thống đang ghi nhận đang mượn]';
            }

            $this->success($msg, '/admin/inventory/audits/' . $id);
        } catch (Throwable $e) {
            $this->fail($e->getMessage(), '/admin/inventory/audits/' . $id);
        }
    }

    public function complete(int $id): void
    {
        validate_csrf();
        $user = require_permission('books.update');
        $notes = trim((string) ($_POST['notes'] ?? ''));

        try {
            $repo = new InventoryAuditRepository($this->db);
            $audit = $repo->completeAudit($id, (int) $user['id'], $notes ?: null);

            (new ActivityRepository($this->db))->log(
                (int) $user['id'],
                'inventory_audit_completed',
                'inventory_audit',
                $id,
                'Hoàn thành đợt kiểm kê: ' . $audit['audit_code']
            );

            $this->success('Đã hoàn tất và chốt biên bản kiểm kê thành công.', '/admin/inventory/audits/' . $id);
        } catch (Throwable $e) {
            $this->fail('Lỗi: ' . $e->getMessage(), '/admin/inventory/audits/' . $id);
        }
    }

    public function export(int $id): void
    {
        require_permission('books.view');
        $repo = new InventoryAuditRepository($this->db);
        $audit = $repo->findAudit($id);
        if (!$audit) {
            http_response_code(404);
            exit;
        }

        $items = $repo->auditItems($id);
        $missing = $repo->missingCopies($id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="kiem-ke-' . $audit['audit_code'] . '.csv"');

        $out = fopen('php://output', 'w');
        // UTF-8 BOM for Excel
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, ['BIÊN BẢN KIỂM KÊ THƯ VIỆN PAPER HAVEN']);
        fputcsv($out, ['Mã đợt:', $audit['audit_code'], 'Tiêu đề:', $audit['title']]);
        fputcsv($out, ['Trạng thái:', $audit['status'], 'Ngày bắt đầu:', $audit['started_at'], 'Người tạo:', $audit['starter_name']]);
        fputcsv($out, ['Tổng sách dự kiến:', $audit['total_expected'], 'Đã quét:', $audit['total_scanned'], 'Khớp vị trí:', $audit['total_matched'], 'Thiếu:', $audit['total_missing'], 'Lệch vị trí:', $audit['total_misplaced'], 'Hư hỏng:', $audit['total_damaged']]);
        fputcsv($out, []);

        fputcsv($out, ['--- DANH SÁCH BẢN SAO ĐÃ QUÉT ---']);
        fputcsv($out, ['Mã bản sao', 'Barcode', 'Tên sách', 'Vị trí kệ hệ thống', 'Vị trí quét', 'Tình trạng hệ thống', 'Tình trạng quét', 'Khớp vị trí', 'Thời gian quét', 'Người quét']);
        foreach ($items as $item) {
            fputcsv($out, [
                $item['copy_code'],
                $item['barcode'] ?? '',
                $item['book_title'],
                $item['expected_location'] ?? '',
                $item['scanned_location'] ?? '',
                $item['expected_condition'],
                $item['scanned_condition'],
                $item['is_matched'] ? 'ĐÚNG' : 'LỆCH VỊ TRÍ',
                $item['scanned_at'],
                $item['scanner_name']
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['--- DANH SÁCH BẢN SAO THIẾU / CHƯA QUÉT ---']);
        fputcsv($out, ['Mã bản sao', 'Barcode', 'Tên sách', 'Vị trí dự kiến', 'Tình trạng', 'Trạng thái lưu hành']);
        foreach ($missing as $m) {
            fputcsv($out, [
                $m['copy_code'],
                $m['barcode'] ?? '',
                $m['book_title'],
                $m['shelf_location'] ?? '',
                $m['item_condition'],
                $m['status']
            ]);
        }

        fclose($out);
        exit;
    }

    public function barcode(string $code): void
    {
        $copy = $this->viewableCopyLabel($code);
        if (!$copy) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Không tìm thấy mã bản sao.';
            exit;
        }
        try {
            $svg = (new BarcodeService())->renderBarcodeSvg((string) ($copy['barcode'] ?: $copy['copy_code']));
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=300');
            echo $svg;
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Mã bản sao không phù hợp chuẩn Code 128.';
        }
        exit;
    }

    public function qr(string $code): void
    {
        $copy = $this->viewableCopyLabel($code);
        if (!$copy) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Không tìm thấy mã bản sao.';
            exit;
        }
        try {
            $svg = (new BarcodeService())->renderQrSvg((string) $copy['copy_code']);
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, max-age=300');
            echo $svg;
        } catch (\InvalidArgumentException $e) {
            http_response_code(422);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Mã bản sao vượt quá giới hạn QR hiện tại, cần mã ngắn hơn.';
        }
        exit;
    }

    private function viewableCopyLabel(string $code): ?array
    {
        $copy = (new CopyRepository($this->db))->findForLabel($code);
        if (!$copy || !(bool) $copy['is_active']) return null;
        if ((bool) $copy['is_public']) return $copy;
        $role = (string) (current_user()['role'] ?? '');
        return in_array($role, ['admin', 'librarian'], true) && can('books.view') ? $copy : null;
    }

    public function printLabelsHub(): void
    {
        require_permission('books.view');

        $search = query_string('q');
        $categoryId = max(0, (int) ($_GET['category_id'] ?? 0));
        $shelf = trim((string) ($_GET['shelf'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $bookId = max(0, (int) ($_GET['book_id'] ?? 0));

        $book = null;
        $where = ['b.is_active = 1'];
        $params = [];

        if ($bookId > 0) {
            $book = (new BookRepository($this->db))->find($bookId);
            if ($book) {
                $where[] = 'cp.book_id = :book_id';
                $params['book_id'] = $bookId;
            }
        }

        if ($search !== '') {
            $where[] = '(b.title LIKE :s_title OR b.author LIKE :s_author OR b.isbn LIKE :s_isbn OR b.book_code LIKE :s_bcode OR cp.copy_code LIKE :s_code OR cp.barcode LIKE :s_barcode OR cp.shelf_location LIKE :s_shelf)';
            $kw = '%' . $search . '%';
            $params['s_title'] = $kw;
            $params['s_author'] = $kw;
            $params['s_isbn'] = $kw;
            $params['s_bcode'] = $kw;
            $params['s_code'] = $kw;
            $params['s_barcode'] = $kw;
            $params['s_shelf'] = $kw;
        }

        if ($categoryId > 0) {
            $where[] = 'b.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($shelf !== '') {
            if ($shelf === '__none__') {
                $where[] = '(cp.shelf_location IS NULL OR cp.shelf_location = "")';
            } else {
                $where[] = 'cp.shelf_location = :shelf';
                $params['shelf'] = $shelf;
            }
        }

        if ($status !== '' && in_array($status, ['available', 'on_loan', 'reserved', 'pending_audit', 'damaged', 'lost'], true)) {
            $where[] = 'cp.status = :status';
            $params['status'] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM book_copies cp
            JOIN books b ON b.id = cp.book_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $totalCopies = (int) $countStmt->fetchColumn();

        $requestedPerPage = $_GET['per_page'] ?? '30';
        $perPage = $requestedPerPage === 'all' ? max(1, $totalCopies) : max(10, min(150, (int) $requestedPerPage));
        $pagination = new \Library\Core\Pagination($totalCopies, $perPage);

        $listStmt = $this->db->prepare("
            SELECT cp.id, cp.copy_code, cp.barcode, cp.shelf_location, cp.item_condition, cp.status,
                   b.id AS book_id, b.title AS book_title, b.author, b.isbn, b.book_code, b.cover_url,
                   c.name AS category_name
            FROM book_copies cp
            JOIN books b ON b.id = cp.book_id
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE {$whereSql}
            ORDER BY cp.shelf_location ASC, b.title ASC, cp.copy_code ASC
            LIMIT " . (int) $pagination->perPage . " OFFSET " . (int) $pagination->offset . "
        ");
        $listStmt->execute($params);
        $copies = $listStmt->fetchAll(\PDO::FETCH_ASSOC);

        $categories = $this->db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(\PDO::FETCH_ASSOC);
        $shelves = $this->db->query("
            SELECT DISTINCT shelf_location 
            FROM book_copies 
            WHERE shelf_location IS NOT NULL AND shelf_location != '' 
            ORDER BY shelf_location
        ")->fetchAll(\PDO::FETCH_COLUMN);

        $pageTitle = $book ? ('In tem mã sách: ' . $book['title']) : 'In tem & Mã vạch';

        $this->render('admin/print_labels', [
            'title' => $pageTitle,
            'copies' => $copies,
            'pagination' => $pagination,
            'categories' => $categories,
            'shelves' => $shelves,
            'search' => $search,
            'categoryId' => $categoryId,
            'shelf' => $shelf,
            'status' => $status,
            'bookId' => $bookId,
            'totalCopies' => $totalCopies,
            'book' => $book,
            'perPage' => $perPage,
        ], 'layouts/app');
    }

    public function printLabels(int $bookId): void
    {
        require_permission('books.view');
        $_GET['book_id'] = (string) $bookId;
        $this->printLabelsHub();
    }
}
