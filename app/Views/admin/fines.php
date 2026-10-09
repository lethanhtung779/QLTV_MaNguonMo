<?php
$totalOutstanding = array_sum(array_map(static fn(array $account): float => (float) $account['balance'], $accounts));
?>
<div class="page-title-row">
  <div><span class="eyebrow">SỔ THU PHÍ THƯ VIỆN</span><h1>Phí & thanh toán</h1><p>Theo dõi khoản phí theo từng bản mượn, ghi nhận thu tiền và điều chỉnh có lý do.</p></div>
</div>
<section class="metrics-grid">
  <article class="metric-card"><span class="metric-icon metric-overdue">◷</span><span class="metric-label">Độc giả còn dư nợ</span><strong><?= count($accounts) ?></strong><span class="metric-note">Các khoản đã được cập nhật đến hôm nay</span></article>
  <article class="metric-card"><span class="metric-icon metric-copy">✦</span><span class="metric-label">Tổng dư nợ hiện tại</span><strong><?= number_format($totalOutstanding, 0, ',', '.') ?><small class="currency">đ</small></strong><span class="metric-note">Sau khi trừ thanh toán và miễn giảm</span></article>
  <article class="metric-card"><span class="metric-icon metric-available">↗</span><span class="metric-label">Lịch sử giao dịch</span><strong><?= count($payments) ?></strong><span class="metric-note">Các lần thu được ghi nhận gần đây</span></article>
</section>
<div class="dashboard-columns">
  <section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">THU TẠI THƯ VIỆN</span><h2>Ghi nhận thanh toán</h2><p class="muted">Khoản thu sẽ được phân bổ theo phí phát sinh cũ nhất và không vượt dư nợ.</p></div></div>
    <form class="form-stack" method="post" action="<?= e(url('/admin/fines/payments')) ?>">
      <?= csrf_field() ?>
      <label>Độc giả
        <select name="reader_id" required>
          <option value="">Chọn độc giả</option>
          <?php foreach ($readers as $reader): ?>
            <option value="<?= (int) $reader['id'] ?>" <?= $selectedReaderId === (int) $reader['id'] ? 'selected' : '' ?>><?= e($reader['card_number'] . ' · ' . $reader['full_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="form-grid">
        <label>Số tiền (đ)<input type="number" name="amount" min="0.01" max="99999999.99" step="0.01" required></label>
        <label>Hình thức
          <select name="method" required>
            <option value="cash">Tiền mặt</option><option value="bank_transfer">Chuyển khoản</option><option value="card">Thẻ</option><option value="other">Khác</option>
          </select>
        </label>
        <label>Mã tham chiếu<input type="text" name="reference_code" maxlength="120"></label>
        <label>Ghi chú<input type="text" name="note" maxlength="300"></label>
      </div>
      <div class="dialog-actions"><button class="button button-primary">Ghi nhận thanh toán</button></div>
    </form>
  </section>
  <section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">CẦN THEO DÕI</span><h2>Dư nợ độc giả</h2></div></div>
    <?php if ($accounts): ?>
      <div class="table-scroll"><table><thead><tr><th>Độc giả</th><th>Khoản còn mở</th><th>Dư nợ</th></tr></thead><tbody>
        <?php foreach ($accounts as $account): ?>
          <tr><td><strong><?= e($account['full_name']) ?></strong><small><?= e($account['card_number']) ?><?= $account['email'] ? ' · ' . e($account['email']) : '' ?></small></td><td><?= (int) $account['open_charges'] ?></td><td><strong><?= number_format((float) $account['balance'], 0, ',', '.') ?>đ</strong></td></tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php else: ?><div class="empty-inline">Hiện không có dư nợ phí.</div><?php endif; ?>
  </section>
</div>
<section class="panel">
  <div class="panel-heading">
    <div><span class="eyebrow">SỔ CHI TIẾT</span><h2>Khoản phí theo độc giả</h2></div>
    <form class="inline-filter" method="get" action="<?= e(url('/admin/fines')) ?>">
      <label class="sr-only" for="fineReader">Chọn độc giả</label>
      <select id="fineReader" name="reader">
        <option value="">Chọn độc giả để xem</option>
        <?php foreach ($readers as $reader): ?>
          <option value="<?= (int) $reader['id'] ?>" <?= $selectedReaderId === (int) $reader['id'] ? 'selected' : '' ?>><?= e($reader['card_number'] . ' · ' . $reader['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="button button-small button-outline">Xem chi tiết</button>
    </form>
  </div>
  <?php if ($selectedReaderId < 1): ?>
    <div class="empty-inline">Chọn độc giả để xem chi tiết từng khoản phát sinh.</div>
  <?php elseif (!$charges): ?>
    <div class="empty-inline">Độc giả này hiện không có khoản phí nào.</div>
  <?php else: ?>
    <div class="table-scroll"><table><thead><tr><th>Khoản phí</th><th>Loại</th><th>Phát sinh</th><th>Đã thu</th><th>Miễn giảm</th><th>Còn lại</th><?php if (current_user()['role'] === 'admin'): ?><th>Điều chỉnh</th><?php endif; ?></tr></thead><tbody>
      <?php foreach ($charges as $charge): ?>
        <tr>
          <td><strong><?= e($charge['title']) ?></strong><small><?= e($charge['copy_code']) ?> · <?= e($charge['description']) ?></small></td>
          <td><?= ['overdue' => 'Quá hạn', 'damaged' => 'Sách hỏng', 'lost' => 'Sách mất'][$charge['charge_type']] ?? e($charge['charge_type']) ?></td>
          <td><?= number_format((float) $charge['amount'], 0, ',', '.') ?>đ</td>
          <td><?= number_format((float) $charge['paid_amount'], 0, ',', '.') ?>đ</td>
          <td><?= number_format((float) $charge['waived_amount'], 0, ',', '.') ?>đ</td>
          <td><strong><?= number_format((float) $charge['balance'], 0, ',', '.') ?>đ</strong></td>
          <?php if (current_user()['role'] === 'admin'): ?>
            <td><?php if ((float) $charge['balance'] > 0): ?><form class="inline-filter" method="post" action="<?= e(url('/admin/fines/waive')) ?>" data-confirm="Ghi nhận miễn/giảm khoản phí này?"><?= csrf_field() ?><input type="hidden" name="charge_id" value="<?= (int) $charge['id'] ?>"><input type="number" name="amount" min="0.01" max="<?= e((string) $charge['balance']) ?>" step="0.01" placeholder="Số tiền" required><input type="text" name="reason" maxlength="300" placeholder="Lý do" required><button class="button button-small button-outline">Áp dụng</button></form><?php else: ?>—<?php endif; ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</section>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">GIAO DỊCH GẦN ĐÂY</span><h2>Lịch sử thanh toán</h2></div></div>
  <?php if ($payments): ?>
    <div class="table-scroll"><table><thead><tr><th>Thời điểm</th><th>Độc giả</th><th>Số tiền</th><th>Hình thức</th><th>Tham chiếu</th><th>Người nhận</th></tr></thead><tbody>
      <?php foreach ($payments as $payment): ?>
        <tr><td><?= e(date('d/m/Y H:i', strtotime($payment['created_at']))) ?></td><td><strong><?= e($payment['full_name']) ?></strong><small><?= e($payment['card_number']) ?></small></td><td><strong><?= number_format((float) $payment['amount'], 0, ',', '.') ?>đ</strong></td><td><?= ['cash' => 'Tiền mặt', 'bank_transfer' => 'Chuyển khoản', 'card' => 'Thẻ', 'other' => 'Khác'][$payment['method']] ?? e($payment['method']) ?></td><td><?= e($payment['reference_code'] ?: '—') ?></td><td><?= e($payment['receiver_name'] ?: '—') ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><div class="empty-inline">Chưa có giao dịch thanh toán nào.</div><?php endif; ?>
</section>
