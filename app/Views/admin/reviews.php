<div class="panel-header">
  <div>
    <span class="eyebrow">QUẢN TRỊ NỘI DUNG</span>
    <h1>Đánh giá & Nhận xét của độc giả</h1>
    <p>Kiểm duyệt các đánh giá sao và nhận xét sách do độc giả gửi lên hệ thống Paper Haven.</p>
  </div>
</div>

<section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5;">
  <div class="section-heading">
    <div>
      <span class="eyebrow">DANH SÁCH ĐÁNH GIÁ</span>
      <h2>Tất cả nhận xét (<?= count($reviews) ?>)</h2>
    </div>
  </div>

  <?php if ($reviews): ?>
    <div class="table-wrap">
      <table class="data-table" style="width: 100%;">
        <thead>
          <tr>
            <th>Thời gian</th>
            <th>Sách</th>
            <th>Độc giả</th>
            <th>Đánh giá</th>
            <th>Nội dung nhận xét</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reviews as $rev): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($rev['created_at']))) ?></td>
              <td>
                <a href="<?= e(url('/book/' . (int) $rev['book_id'])) ?>" target="_blank" style="font-weight: 600; color: #193E30;">
                  <?= e($rev['book_title']) ?> ↗
                </a>
                <br><small style="color: #74685c;"><?= e($rev['book_code']) ?></small>
              </td>
              <td>
                <strong><?= e($rev['reader_name']) ?></strong>
                <br><small style="color: #74685c;">Thẻ: <?= e($rev['card_number']) ?></small>
              </td>
              <td>
                <span style="color: #c58b54; font-size: 1.1rem; white-space: nowrap;">
                  <?= str_repeat('★', (int) $rev['rating']) . str_repeat('☆', 5 - (int) $rev['rating']) ?>
                </span>
              </td>
              <td style="max-width: 320px;">
                <div style="font-style: italic; color: #302B25;">
                  <?= nl2br(e($rev['comment'] ?: 'Chỉ chấm sao, không viết nhận xét.')) ?>
                </div>
              </td>
              <td>
                <span class="status-badge <?= $rev['is_approved'] ? 'badge-success' : 'badge-warning' ?>">
                  <?= $rev['is_approved'] ? 'Hiển thị công khai' : 'Đang ẩn' ?>
                </span>
              </td>
              <td style="white-space: nowrap;">
                <form action="<?= e(url('/admin/reviews/moderate')) ?>" method="post" style="display: inline-block;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                  <?php if (!$rev['is_approved']): ?>
                    <input type="hidden" name="action" value="approve">
                    <button class="button button-quiet button-small" type="submit" style="color: #28a745;">Duyệt hiển thị</button>
                  <?php else: ?>
                    <input type="hidden" name="action" value="unapprove">
                    <button class="button button-quiet button-small" type="submit" style="color: #c58b54;">Ẩn đi</button>
                  <?php endif; ?>
                </form>
                <form action="<?= e(url('/admin/reviews/moderate')) ?>" method="post" style="display: inline-block; margin-left: 0.25rem;" data-confirm="Bạn có chắc chắn muốn xóa đánh giá này?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="review_id" value="<?= (int) $rev['id'] ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="button button-quiet button-small" type="submit" style="color: #dc3545;">Xóa</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-state" style="padding: 2.5rem; text-align: center; background: #F8F3EB; border-radius: 6px;">
      <span style="font-size: 2rem;">★</span>
      <h3 style="margin-top: 0.5rem; color: #193E30;">Chưa có đánh giá nào từ độc giả</h3>
      <p style="color: #74685c;">Khi bạn đọc gửi đánh giá trên trang chi tiết sách, các nhận xét sẽ hiển thị tại đây để quản trị viên theo dõi.</p>
    </div>
  <?php endif; ?>
</section>
