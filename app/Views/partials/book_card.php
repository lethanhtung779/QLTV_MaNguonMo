<article class="book-card">
  <a class="book-cover-wrap" href="<?= e(url('/book/' . $book['id'])) ?>">
    <?php $cover = book_cover($book); ?>
    <?php if ($cover): ?><img class="book-cover" src="<?= e($cover) ?>" alt="Bìa sách <?= e($book['title']) ?>" loading="lazy" data-cover-fallback="<?= e(cover_fallback($book)) ?>"><?php else: ?><span class="book-cover book-cover-fallback <?= e(cover_fallback($book)) ?>"><span><?= e(mb_strtoupper(mb_substr($book['title'], 0, 1))) ?></span><small>PAPER & PAGE</small></span><?php endif; ?>
  </a>
  <div class="book-card-copy">
    <span class="eyebrow"><?= e($book['category_name'] ?? 'Sách') ?></span>
    <h3><a href="<?= e(url('/book/' . $book['id'])) ?>"><?= e($book['title']) ?></a></h3>
    <p><?= e($book['author']) ?></p>
    <div class="book-card-bottom"><span class="availability <?= (int) ($book['available_copies'] ?? 0) > 0 ? 'is-available' : 'is-unavailable' ?>"><?= (int) ($book['available_copies'] ?? 0) > 0 ? 'Còn ' . (int) $book['available_copies'] . ' bản' : 'Đang được mượn' ?></span><a class="text-link" href="<?= e(url('/book/' . $book['id'])) ?>">Chi tiết <span>→</span></a></div>
  </div>
</article>
