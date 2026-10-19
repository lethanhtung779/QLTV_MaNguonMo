<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Core\Config;
use Library\Repositories\BookRepository;
use Library\Repositories\CategoryRepository;
use Library\Repositories\SystemSettingRepository;

final class CatalogController extends Controller
{
    public function home(): void
    {
        $books = new BookRepository($this->db);
        $policy = (new SystemSettingRepository($this->db))->currentPolicy();
        $search = query_string('q');
        $categoryRaw = $_GET['category'] ?? 0;
        $categoryId = is_string($categoryRaw) || is_int($categoryRaw)
            ? filter_var($categoryRaw, FILTER_VALIDATE_INT) : false;
        $categoryId = $categoryId === false ? 0 : max(0, min(1000000, $categoryId));
        $sort = query_string('sort');
        $allowedSorts = ['title', 'newest', 'popular', 'rating'];
        $sortOrder = in_array($sort, $allowedSorts, true) ? $sort : 'title';
        $pageParam = isset($_GET['books_page']) ? 'books_page' : 'page';
        $totalPublic = $books->countSearch($search, $categoryId, '', true);
        $pagination = new \Library\Core\Pagination($totalPublic, 12, $pageParam);
        $this->render('public/home', [
            'title' => 'Sách mở ra những thế giới mới',
            'categories' => $books->allCategories(true),
            'featuredBooks' => $books->featured(5),
            'popularBooks' => $books->publicPopular(5),
            'recommendedBooks' => (new \Library\Services\RecommendationService($this->db))->getRecommendations(current_user(), 0, 6),
            'categoryCollections' => $books->publicCategoryCollections(),
            'homeBooks' => $books->search($search, $categoryId, '', $pagination->perPage, $pagination->offset, $sortOrder, true),
            'pagination' => $pagination,
            'search' => $search,
            'categoryId' => $categoryId,
            'sort' => $sort,
            'newBooks' => $books->search('', 0, '', 6, 0, 'newest', true),
            'registrationOpen' => Config::bool('ALLOW_PUBLIC_REGISTRATION', true),
            'policy' => $policy,
        ], 'layouts/public');
    }

    public function index(): void
    {
        $books = new BookRepository($this->db);
        $categories = new CategoryRepository($this->db);
        $search = query_string('q');
        $categoryRaw = $_GET['category'] ?? 0;
        $categoryId = is_string($categoryRaw) || is_int($categoryRaw)
            ? filter_var($categoryRaw, FILTER_VALIDATE_INT) : false;
        $categoryId = $categoryId === false ? 0 : max(0, min(1000000, $categoryId));
        $availability = is_string($_GET['availability'] ?? null) &&
            in_array($_GET['availability'], ['available', 'unavailable'], true)
            ? $_GET['availability'] : '';
        $publisher = query_string('publisher');
        $allowedPublishers = $books->publicPublishers();
        if (!in_array($publisher, $allowedPublishers, true)) $publisher = '';
        $yearRaw = $_GET['year'] ?? 0;
        $year = (is_string($yearRaw) || is_int($yearRaw)) ? filter_var($yearRaw, FILTER_VALIDATE_INT) : false;
        $allowedYears = array_map('intval', $books->publicPublicationYears());
        $year = $year !== false && in_array((int) $year, $allowedYears, true) ? (int) $year : 0;
        $pagination = new \Library\Core\Pagination($books->countSearch($search, $categoryId, $availability, true, $publisher, $year));
        $sort = query_string('sort');
        $view = query_string('view') === 'list' ? 'list' : 'grid';
        $this->render('public/catalog', [
            'title' => 'Danh mục sách',
            'books' => $books->search($search, $categoryId, $availability, $pagination->perPage, $pagination->offset, $sort, true, $publisher, $year),
            'pagination' => $pagination,
            'sort' => $sort,
            'categories' => $categories->all(true),
            'search' => $search,
            'categoryId' => $categoryId,
            'availability' => $availability,
            'publishers' => $allowedPublishers,
            'publisher' => $publisher,
            'publicationYears' => $allowedYears,
            'publicationYear' => $year,
            'view' => $view,
        ], 'layouts/public');
    }

    public function show(int $id): void
    {
        $bookRepo = new BookRepository($this->db);
        $user = current_user();
        $role = (string) ($user['role'] ?? '');
        $publicOnly = !in_array($role, ['admin', 'librarian'], true);
        $book = $bookRepo->find($id, $publicOnly);
        if (!$book) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Không tìm thấy sách'], 'layouts/public');
            return;
        }

        $readerId = (int) ($user['reader_id'] ?? 0);
        $isFavorite = false;
        $userReview = null;

        if ($readerId > 0) {
            $isFavorite = (new \Library\Repositories\FavoriteRepository($this->db))->isFavorite($readerId, $id);
            $userReview = (new \Library\Repositories\ReviewRepository($this->db))->userReview($id, $readerId);
        }

        $reviewRepo = new \Library\Repositories\ReviewRepository($this->db);
        $reviews = $reviewRepo->forBook($id, true);
        $reviewSummary = $reviewRepo->summaryForBook($id);

        $copyRepo = new \Library\Repositories\CopyRepository($this->db);
        $copies = $copyRepo->all($id);

        $similarBooks = (new \Library\Services\RecommendationService($this->db))->getRecommendations($user, $id, 4);

        $this->render('public/book', [
            'title' => $book['title'],
            'book' => $book,
            'isFavorite' => $isFavorite,
            'userReview' => $userReview,
            'reviews' => $reviews,
            'reviewSummary' => $reviewSummary,
            'copies' => $copies,
            'similarBooks' => $similarBooks,
        ], 'layouts/public');
    }
}
