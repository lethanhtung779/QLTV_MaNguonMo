<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Repositories\NotificationRepository;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = require_auth(['admin', 'librarian', 'reader']);
        (new \Library\Services\ReminderService($this->db))->synchronize((int)$user['id']);
        $this->render('notifications/index', [
            'title' => 'Thông báo',
            'notifications' => (new NotificationRepository($this->db))->recentForUser((int) $user['id']),
        ]);
    }

    public function markRead(): never
    {
        $user = require_auth(['admin', 'librarian', 'reader']);
        validate_csrf();
        $repository = new NotificationRepository($this->db);
        if (($_POST['mark_all'] ?? '') === '1') {
            $repository->markAllRead((int) $user['id']);
            $this->success('Đã đánh dấu tất cả thông báo là đã đọc.', '/notifications');
        }
        $id = request_int('id', 1);
        if (!$repository->markRead($id, (int) $user['id'])) {
            $this->fail('Không tìm thấy thông báo cần cập nhật.', '/notifications');
        }
        $this->success('Đã đánh dấu thông báo là đã đọc.', '/notifications');
    }
}
