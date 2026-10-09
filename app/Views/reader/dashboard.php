<div class="page-title-row"><div><span class="eyebrow">THẺ ĐỘC GIẢ · <?= e($reader['card_number'] ?? '') ?></span><h1>Chào bạn, <?= e($reader['full_name'] ?? $user['name']) ?>.</h1><p>Những trang sách bạn đang khám phá và hành trình tiếp theo.</p></div><a class="button button-primary" href="<?= e(url('/catalog')) ?>">Khám phá sách <span>→</span></a></div>
<section class="metrics-grid reader-metrics">
  <article class="metric-card"><span class="metric-icon metric-loans">⇄</span><span class="metric-label">Sách đang mượn</span><strong><?= count(array_filter($loans, static fn($loan) => !$loan['returned_at'])) ?></strong><span class="metric-note">Hãy nhớ ngày hẹn trả</span></article>
  <article class="metric-card"><span class="metric-icon metric-overdue">◷</span><span class="metric-label">Sách quá hạn</span><strong><?= count(array_filter($loans, static fn($loan) => $loan['loan_status'] === 'overdue')) ?></strong><span class="metric-note">Theo dõi lịch mượn bên dưới</span></article>
  <article class="metric-card"><span class="metric-icon metric-available">↗</span><span class="metric-label">Yêu cầu đặt trước</span><strong><?= count(array_filter($reservations, static fn($reservation) => in_array($reservation['status'], ['pending', 'ready'], true))) ?></strong><span class="metric-note">Thư viện sẽ báo khi sách sẵn sàng</span></article>
  <article class="metric-card"><span class="metric-icon metric-copy">✦</span><span class="metric-label">Phí còn phải thanh toán</span><strong><?= number_format((float) $fines, 0, ',', '.') ?><small class="currency">đ</small></strong><span class="metric-note">Thanh toán tại quầy thư viện</span></article>
</section>
<?php if ($fineCharges): ?>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">TÀI KHOẢN PHÍ</span><h2>Chi tiết khoản phí</h2><p class="muted">Các khoản thu, thanh toán và miễn giảm được thư viện ghi nhận.</p></div></div>
  <div class="table-scroll"><table><thead><tr><th>Sách / bản sao</th><th>Loại phí</th><th>Phát sinh</th><th>Đã thanh toán</th><th>Miễn giảm</th><th>Còn lại</th></tr></thead><tbody>
    <?php foreach ($fineCharges as $charge): ?>
      <tr>
        <td><strong><?= e($charge['title']) ?></strong><small><?= e($charge['copy_code']) ?></small></td>
        <td><?= ['overdue' => 'Quá hạn', 'damaged' => 'Sách hỏng', 'lost' => 'Sách mất'][$charge['charge_type']] ?? e($charge['charge_type']) ?></td>
        <td><?= number_format((float) $charge['amount'], 0, ',', '.') ?>đ</td>
        <td><?= number_format((float) $charge['paid_amount'], 0, ',', '.') ?>đ</td>
        <td><?= number_format((float) $charge['waived_amount'], 0, ',', '.') ?>đ</td>
        <td><strong><?= number_format((float) $charge['balance'], 0, ',', '.') ?>đ</strong></td>
      </tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
<?php if ($finePayments): ?>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">BIÊN NHẬN CỦA BẠN</span><h2>Lịch sử thanh toán phí</h2></div></div>
  <div class="table-scroll"><table><thead><tr><th>Thời điểm</th><th>Số tiền</th><th>Hình thức</th><th>Mã tham chiếu</th><th>Người tiếp nhận</th></tr></thead><tbody>
    <?php foreach ($finePayments as $payment): ?>
      <tr><td><?= e(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td><td><strong><?= number_format((float) $payment['amount'], 0, ',', '.') ?>đ</strong></td><td><?= ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'card' => 'Thẻ', 'other' => 'Khác'][$payment['method']] ?? e($payment['method']) ?></td><td><?= e($payment['reference_code'] ?: '—') ?></td><td><?= e($payment['receiver_name'] ?: '—') ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">HÀNH TRÌNH ĐỌC CỦA BẠN</span><h2>Sách đang mượn & lịch sử</h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Tìm sách mới <span>→</span></a></div>
  <?php if ($loans): ?>
    <div class="table-scroll"><table><thead><tr><th>Sách / bản sao</th><th>Ngày mượn</th><th>Hạn trả</th><th>Ngày trả</th><th>Trạng thái</th><th>Phí tạm tính</th><th></th></tr></thead><tbody>
      <?php foreach ($loans as $loan): ?>
        <tr>
          <td><strong><?= e($loan['book_title']) ?></strong><small><?= e($loan['book_code']) ?> · <?= e($loan['copy_code']) ?></small></td>
          <td><?= e(format_date($loan['borrowed_at'])) ?></td>
          <td><?= e(format_date($loan['due_at'])) ?></td>
          <td><?= e(format_date($loan['returned_at'])) ?></td>
          <td><span class="status-pill <?= e($loan['loan_status']) ?>"><?= $loan['loan_status'] === 'returned' ? 'Đã trả' : ($loan['loan_status'] === 'overdue' ? 'Quá hạn' : 'Đang mượn') ?></span></td>
          <td><?= (float) $loan['fine_amount'] > 0 ? number_format((float) $loan['fine_amount'], 0, ',', '.') . 'đ' : '—' ?></td>
          <td><?php if (!$loan['returned_at'] && $loan['loan_status'] === 'borrowed' && (int) ($loan['renewal_count'] ?? 0) < $maxRenewals): ?><form method="post" action="<?= e(url('/account/loans/renew')) ?>" data-confirm="Gia hạn riêng bản sao này?"><?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $loan['loan_item_id'] ?>"><button class="button button-small button-outline">Gia hạn <?= (int) ($loan['renewal_count'] ?? 0) ?>/<?= (int) $maxRenewals ?></button></form><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><div class="empty-inline">Bạn chưa có phiếu mượn. <a class="table-link" href="<?= e(url('/catalog')) ?>">Khám phá danh mục sách →</a></div><?php endif; ?>
</section>
<section class="panel reservation-panel">
  <div class="panel-heading"><div><span class="eyebrow">DANH SÁCH CHỜ</span><h2>Yêu cầu đặt trước</h2></div></div>
  <?php if ($reservations): ?>
    <div class="table-scroll"><table><thead><tr><th>Sách / bản sao</th><th>Ngày yêu cầu</th><th>Hạn nhận sách</th><th>Trạng thái</th><th></th></tr></thead><tbody>
      <?php foreach ($reservations as $reservation): ?>
        <tr>
          <td><strong><?= e($reservation['title']) ?></strong><small><?= e($reservation['author']) ?><?= !empty($reservation['copy_code']) ? ' · Bản giữ ' . e($reservation['copy_code']) : '' ?></small></td>
          <td><?= e(date('d/m/Y', strtotime($reservation['reserved_at']))) ?></td>
          <td><?= e($reservation['expires_at'] ? date('d/m/Y', strtotime($reservation['expires_at'])) : '—') ?></td>
          <td><span class="status-pill <?= e($reservation['status']) ?>"><?= ['pending' => 'Đang chờ', 'ready' => 'Có thể nhận', 'fulfilled' => 'Đã hoàn tất', 'cancelled' => 'Đã hủy'][$reservation['status']] ?? e($reservation['status']) ?></span></td>
          <td><?php if (in_array($reservation['status'], ['pending', 'ready'], true)): ?><form method="post" action="<?= e(url('/account/reservations/cancel')) ?>" data-confirm="Hủy yêu cầu đặt trước này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><button class="icon-button danger-button">Hủy</button></form><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><div class="empty-inline">Bạn chưa có yêu cầu đặt trước nào.</div><?php endif; ?>
</section>
<section class="content-section reader-recommendations">
  <div class="section-heading"><div><span class="eyebrow">CÓ THỂ BẠN SẼ THÍCH</span><h2>Tiếp tục <em>khám phá</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Tất cả sách <span>→</span></a></div>
  <?php if ($books): ?><div class="book-grid"><?php foreach (array_slice($books, 0, 4) as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Danh mục sách đang được cập nhật.</p><?php endif; ?>
</section>
