<section class="book-detail-page">
  <a class="back-link" href="<?= e(url('/catalog')) ?>">← Trở về danh mục sách</a>
  <div class="book-detail">
    <?php $cover = book_cover($book, 'L'); ?>
    <div class="book-detail-cover"><?php if ($cover): ?><img src="<?= e($cover) ?>" alt="Bìa sách <?= e($book['title']) ?>" data-cover-fallback="<?= e(cover_fallback($book)) ?>"><?php else: ?><span class="book-cover-fallback <?= e(cover_fallback($book)) ?>"><span><?= e(mb_strtoupper(mb_substr($book['title'], 0, 1))) ?></span><small>PAPER & PAGE</small></span><?php endif; ?></div>
    <div class="book-detail-copy"><span class="eyebrow"><?= e($book['category_name'] ?? 'Sách thư viện') ?></span><h1><?= e($book['title']) ?></h1><p class="book-author">Tác giả <strong><?= e($book['author']) ?></strong></p><div class="detail-status"><span class="availability <?= (int) $book['available_copies'] > 0 ? 'is-available' : 'is-unavailable' ?>"><?= (int) $book['available_copies'] > 0 ? 'Hiện còn ' . (int) $book['available_copies'] . ' bản có thể mượn' : 'Tất cả bản đang được mượn' ?></span><small><?= (int) $book['total_copies'] ?> bản trong thư viện</small></div><dl class="book-metadata"><?php if ($book['publisher']): ?><div><dt>Nhà xuất bản</dt><dd><?= e($book['publisher']) ?></dd></div><?php endif; ?><?php if ($book['publication_year']): ?><div><dt>Năm xuất bản</dt><dd><?= e($book['publication_year']) ?></dd></div><?php endif; ?><?php if ($book['isbn']): ?><div><dt>ISBN</dt><dd><?= e($book['isbn']) ?></dd></div><?php endif; ?><div><dt>Mã sách</dt><dd><?= e($book['book_code']) ?></dd></div></dl><?php if ($book['description']): ?><div class="book-description"><h2>Về cuốn sách</h2><p><?= nl2br(e($book['description'])) ?></p></div><?php endif; ?>
      <?php $user = current_user(); if ((int) $book['available_copies'] < 1): ?>
        <?php if (($user['role'] ?? '') === 'reader'): ?><form action="<?= e(url('/account/reservations')) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>"><button class="button button-primary">Đặt trước cuốn sách</button></form><?php elseif (!$user): ?><a class="button button-primary" href="<?= e(url('/login')) ?>">Đăng nhập để đặt trước</a><?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
