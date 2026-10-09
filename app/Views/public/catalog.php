<section class="page-intro"><span class="eyebrow">DANH MỤC THƯ VIỆN</span><h1>Mở một cuốn sách,<br><em>mở một chân trời.</em></h1><p>Khám phá những câu chuyện và tri thức đang chờ bạn trên từng trang sách.</p></section>
<section class="catalog-section">
  <form class="filter-bar" method="get" action="<?= e(url('/catalog')) ?>">
    <label class="catalog-search"><span>⌕</span><input type="search" name="q" placeholder="Tên sách, tác giả, ISBN..." value="<?= e($search) ?>"></label>
    <label><span class="sr-only">Thể loại</span><select name="category"><option value="0">Tất cả thể loại</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>"<?= selected($categoryId, $category['id']) ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></label>
    <label><span class="sr-only">Tình trạng</span><select name="availability"><option value="">Mọi tình trạng</option><option value="available"<?= selected($availability, 'available') ?>>Còn sách</option><option value="unavailable"<?= selected($availability, 'unavailable') ?>>Đang được mượn</option></select></label>
    <label><span class="sr-only">Sắp xếp</span><select name="sort"><option value="newest"<?= selected($sort, 'newest') ?>>Mới cập nhật</option><option value="title"<?= selected($sort, 'title') ?>>Tên sách</option><option value="author"<?= selected($sort, 'author') ?>>Tác giả</option></select></label><button class="button button-primary" type="submit">Lọc sách</button>
  </form>
  <?php if ($books): ?><p class="results-count">Tìm thấy <strong><?= (int) $pagination->total ?></strong> đầu sách</p><div class="book-grid catalog-grid"><?php foreach ($books as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div>
  <?php else: ?><div class="empty-state"><span>⌕</span><h2>Chưa tìm thấy cuốn sách phù hợp</h2><p>Thử từ khóa khác hoặc bỏ bớt bộ lọc nhé.</p><a href="<?= e(url('/catalog')) ?>" class="button button-outline">Xóa bộ lọc</a></div><?php endif; ?>
</section>

<?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
