<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Repositories\ActivityRepository;
use Library\Repositories\BookRepository;
use Library\Repositories\FavoriteRepository;
use Library\Repositories\ReaderRepository;
use Library\Repositories\ReviewRepository;
use RuntimeException;
use Throwable;

final class ReviewController extends Controller
{
    public function toggleFavorite(int $bookId): void
    {
        validate_csrf();
        $user = require_auth(['reader']);
        $reader = (new ReaderRepository($this->db))->findByUser((int) $user['id']);
        if (!$reader) {
            $this->fail('Không tìm thấy hồ sơ độc giả.', '/login');
        }

        $book = (new BookRepository($this->db))->find($bookId);
        if (!$book) {
            $this->fail('Không tìm thấy sách.', '/catalog');
        }

        $repo = new FavoriteRepository($this->db);
        $added = $repo->toggle((int) $reader['id'], $bookId);

        (new ActivityRepository($this->db))->log(
            (int) $user['id'],
            $added ? 'favorite_added' : 'favorite_removed',
            'book',
            $bookId,
            ($added ? 'Thêm' : 'Bỏ') . ' sách yêu thích: ' . $book['title']
        );

        $msg = $added ? 'Đã thêm cuốn sách vào tủ sách yêu thích của bạn.' : 'Đã bỏ sách khỏi tủ sách yêu thích.';
        $this->success($msg, '/book/' . $bookId);
    }

    public function submitReview(int $bookId): void
    {
        validate_csrf();
        $user = require_auth(['reader']);
        $reader = (new ReaderRepository($this->db))->findByUser((int) $user['id']);
        if (!$reader) {
            $this->fail('Không tìm thấy hồ sơ độc giả.', '/login');
        }

        $book = (new BookRepository($this->db))->find($bookId);
        if (!$book) {
            $this->fail('Không tìm thấy sách.', '/catalog');
        }

        $rating = (int) ($_POST['rating'] ?? 5);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($rating < 1 || $rating > 5) {
            $this->fail('Điểm đánh giá phải từ 1 đến 5 sao.', '/book/' . $bookId);
        }

        if (mb_strlen($comment) > 1000) {
            $this->fail('Nhận xét tối đa 1000 ký tự.', '/book/' . $bookId);
        }

        $repo = new ReviewRepository($this->db);
        $repo->addOrUpdate($bookId, (int) $reader['id'], $rating, $comment);

        (new ActivityRepository($this->db))->log(
            (int) $user['id'],
            'review_submitted',
            'book',
            $bookId,
            'Đánh giá ' . $rating . ' sao cho sách: ' . $book['title']
        );

        $this->success('Cảm ơn bạn đã gửi đánh giá cho cuốn sách!', '/book/' . $bookId);
    }

    public function adminList(): void
    {
        require_permission('books.view');
        $repo = new ReviewRepository($this->db);
        $reviews = $repo->listAll(100, 0);

        $this->render('admin/reviews', [
            'title' => 'Quản lý đánh giá độc giả',
            'reviews' => $reviews,
        ], 'layouts/app');
    }

    public function moderate(): void
    {
        validate_csrf();
        require_permission('books.edit');

        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $action = (string) ($_POST['action'] ?? '');
        $repo = new ReviewRepository($this->db);

        if ($action === 'approve') {
            $repo->approve($reviewId, true);
            $msg = 'Đã phê duyệt đánh giá.';
        } elseif ($action === 'unapprove') {
            $repo->approve($reviewId, false);
            $msg = 'Đã ẩn đánh giá khỏi trang công khai.';
        } elseif ($action === 'delete') {
            $repo->delete($reviewId);
            $msg = 'Đã xóa đánh giá.';
        } else {
            $this->fail('Thao tác không hợp lệ.', '/admin/reviews');
        }

        $this->success($msg, '/admin/reviews');
    }
}
