<article class="book-card">
  <a class="book-cover-wrap" href="<?= e(url('/book/' . $book['id'])) ?>" aria-label="<?= e('Xem chi tiết: ' . ($book['title'] ?? '')) ?>">
    <?= book_cover_markup($book) ?>
  </a>
  <div class="book-card-copy">
    <span class="eyebrow"><?= e($book['category_name'] ?? 'Sách') ?></span>
    <h3><a href="<?= e(url('/book/' . $book['id'])) ?>" title="<?= e($book['title']) ?>"><?= e($book['title']) ?></a></h3>
    <p class="book-card-author" title="<?= e($book['author']) ?>"><?= e($book['author']) ?></p>
    <?php if (!empty($book['recommendation_reason'])): ?>
      <div class="book-card-reason">
        <span>✦</span> <?= e($book['recommendation_reason']) ?>
      </div>
    <?php endif; ?>
    <?php if (isbn_cover_source($book)): ?>
      <small class="muted" style="font-size: 0.72rem; margin-bottom: 4px; display: block;">Bìa bản tiếng Anh theo ISBN</small>
    <?php endif; ?>
    <div class="book-card-bottom">
      <span class="availability <?= (int) ($book['available_copies'] ?? 0) > 0 ? 'is-available' : 'is-unavailable' ?>">
        <?= (int) ($book['available_copies'] ?? 0) > 0 ? 'Còn ' . (int) $book['available_copies'] . ' bản' : 'Chưa có sẵn' ?>
      </span>
      <a class="text-link" href="<?= e(url('/book/' . $book['id'])) ?>">Xem chi tiết <span>→</span></a>
    </div>
    <?php if ((int) ($book['available_copies'] ?? 0) < 1 && (current_user()['role'] ?? '') === 'reader'): ?>
      <a class="button button-small button-outline" style="margin-top: 6px; width: 100%; text-align: center; box-sizing: border-box;" href="<?= e(url('/book/' . $book['id'])) ?>">Đặt trước</a>
    <?php endif; ?>
  </div>
</article>
