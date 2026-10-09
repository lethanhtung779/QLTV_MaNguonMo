<?php
declare(strict_types=1);

namespace Library\Controllers;

use Library\Core\Config;
use Library\Repositories\BookRepository;
use Library\Repositories\CategoryRepository;

final class CatalogController extends Controller
{
    public function home(): void
    {
        $books = new BookRepository($this->db);
        $this->render('public/home', [
            'title' => 'Sách mở ra những thế giới mới',
            'categories' => $books->allCategories(),
            'popular' => $books->popular(5),
            'newBooks' => $books->search('', 0, '', 6),
            'registrationOpen' => Config::bool('ALLOW_PUBLIC_REGISTRATION', true),
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
        $pagination = new \Library\Core\Pagination($books->countSearch($search, $categoryId, $availability));
        $sort = query_string('sort');
        $this->render('public/catalog', [
            'title' => 'Danh mục sách',
            'books' => $books->search($search, $categoryId, $availability, $pagination->perPage, $pagination->offset, $sort),
            'pagination' => $pagination,
            'sort' => $sort,
            'categories' => $categories->all(),
            'search' => $search,
            'categoryId' => $categoryId,
            'availability' => $availability,
        ], 'layouts/public');
    }

    public function show(int $id): void
    {
        $book = (new BookRepository($this->db))->find($id);
        if (!$book) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Không tìm thấy sách'], 'layouts/public');
            return;
        }
        $this->render('public/book', ['title' => $book['title'], 'book' => $book], 'layouts/public');
    }
}
