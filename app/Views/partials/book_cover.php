<?php
$coverTitle = (string) ($book['title'] ?? $book['book_title'] ?? '');
$coverAuthor = (string) ($book['author'] ?? '');
$coverCategory = (string) ($book['category_name'] ?? '');
$coverSize = $size ?? 'M';
$coverSource = book_cover($book, $coverSize);
?>
<?php if ($coverSource): ?><img class="book-cover" src="<?= e($coverSource) ?>" alt="Bìa sách <?= e($coverTitle) ?> — <?= e($coverAuthor) ?>" loading="lazy" decoding="async" data-cover-fallback="placeholder"><?php endif; ?>
<div class="book-cover book-cover-placeholder" data-book-cover-placeholder<?= $coverSource ? ' hidden' : '' ?> role="img" aria-label="Chưa có ảnh bìa cho <?= e($coverTitle) ?>">
  <span class="book-cover-placeholder-icon" aria-hidden="true">▤</span>
  <strong>Chưa có ảnh bìa</strong>
  <small>Đang chờ bổ sung ảnh đã xác minh</small>
</div>


