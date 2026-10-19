<div class="panel-header">
  <div>
    <span class="eyebrow">KHÔNG GIAN CÁ NHÂN</span>
    <h1>Tủ sách của tôi</h1>
    <p>Nơi lưu giữ các tựa sách yêu thích, theo dõi sách đang mượn và hành trình đọc của bạn tại Paper Haven.</p>
  </div>
  <a class="button button-outline" href="<?= e(url('/catalog')) ?>">Khám phá thêm sách ↗</a>
</div>

<section class="admin-section" style="margin-bottom: 2rem;">
  <div class="section-heading">
    <div>
      <span class="eyebrow">DANH MỤC YÊU THÍCH</span>
      <h2>Sách đã lưu (<?= count($favorites) ?>)</h2>
    </div>
  </div>

  <?php if ($favorites): ?>
    <div class="book-grid">
      <?php foreach ($favorites as $book): ?>
        <article class="book-card">
          <a class="book-cover-link" href="<?= e(url('/book/' . (int) $book['id'])) ?>">
            <?= book_cover_markup($book, 'M') ?>
          </a>
          <div class="book-info">
            <span class="book-category"><?= e($book['category_name'] ?? 'Chung') ?></span>
            <h3 class="book-title"><a href="<?= e(url('/book/' . (int) $book['id'])) ?>"><?= e($book['title']) ?></a></h3>
            <p class="book-author"><?= e($book['author']) ?></p>
            <div class="book-meta">
              <span class="availability <?= (int) $book['available_copies'] > 0 ? 'is-available' : 'is-unavailable' ?>">
                <?= (int) $book['available_copies'] > 0 ? 'Còn ' . (int) $book['available_copies'] . ' bản' : 'Hết bản mượn' ?>
              </span>
            </div>
            <form action="<?= e(url('/book/' . (int) $book['id'] . '/favorite')) ?>" method="post" style="margin-top: 0.5rem;">
              <?= csrf_field() ?>
              <button class="button button-quiet button-small" type="submit" style="color: #c58b54;">♥ Bỏ lưu</button>
            </form>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state" style="padding: 2.5rem; text-align: center; background: #F8F3EB; border-radius: 8px; border: 1px dashed #E8D8C5;">
      <span style="font-size: 2rem;">♥</span>
      <h3 style="margin-top: 0.5rem; color: #193E30;">Tủ sách yêu thích chưa có sách nào</h3>
      <p style="color: #74685c;">Hãy dạo qua danh mục thư viện và bấm lưu vào cuốn sách bạn quan tâm.</p>
      <a class="button button-primary" href="<?= e(url('/catalog')) ?>" style="margin-top: 1rem;">Khám phá danh mục ngay →</a>
    </div>
  <?php endif; ?>
</section>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5;">
    <div class="section-heading">
      <div>
        <span class="eyebrow">ĐANG LƯU HÀNH</span>
        <h3>Sách đang mượn (<?= count($loans['active'] ?? []) ?>)</h3>
      </div>
    </div>
    <?php if (!empty($loans['active'])): ?>
      <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($loans['active'] as $loan): ?>
          <li style="padding: 0.75rem; background: #F8F3EB; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <strong style="color: #193E30; display: block;"><?= e($loan['book_title']) ?></strong>
              <small style="color: #74685c;">Mã: <?= e($loan['copy_code']) ?> · Hạn trả: <strong style="color: <?= strtotime($loan['due_date']) < time() ? '#dc3545' : '#193E30' ?>;"><?= e(format_date($loan['due_date'])) ?></strong></small>
            </div>
            <a class="button button-quiet button-small" href="<?= e(url('/account')) ?>">Chi tiết</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="color: #74685c; margin: 0;">Hiện bạn không mượn cuốn sách nào.</p>
    <?php endif; ?>
  </section>

  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5;">
    <div class="section-heading">
      <div>
        <span class="eyebrow">HÀNG CHỜ</span>
        <h3>Sách đặt trước (<?= count($reservations ?? []) ?>)</h3>
      </div>
    </div>
    <?php if (!empty($reservations)): ?>
      <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.75rem;">
        <?php foreach ($reservations as $res): ?>
          <li style="padding: 0.75rem; background: #F8F3EB; border-radius: 6px; display: flex; justify-content: space-between; align-items: center;">
            <div>
              <strong style="color: #193E30; display: block;"><?= e($res['title']) ?></strong>
              <small style="color: #74685c;">Trạng thái: <strong><?= $res['status'] === 'ready' ? '<span style="color: #28a745;">ĐÃ SẴN SÀNG NHẬN</span>' : 'Đang chờ hàng đợi #' . (int) $res['queue_position'] ?></strong></small>
            </div>
            <a class="button button-quiet button-small" href="<?= e(url('/account')) ?>">Xem</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p style="color: #74685c; margin: 0;">Bạn chưa có yêu cầu đặt trước nào.</p>
    <?php endif; ?>
  </section>
</div>
