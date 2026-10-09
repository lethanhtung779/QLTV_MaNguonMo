<div class="page-title-row">
  <div><span class="eyebrow">THEO DÕI MƯỢN VÀ TRẢ</span><h1>Mượn / Trả sách</h1><p>Quản lý phiếu nhiều bản sao, hạn trả, gia hạn và yêu cầu đặt trước.</p></div>
  <button class="button button-primary" type="button" data-dialog-open="loanDialog">＋ Lập phiếu mượn</button>
</div>
<form class="filter-bar staff-filter" method="get" action="<?= e(url('/admin/loans')) ?>">
  <label class="catalog-search"><span>⌕</span><input type="search" name="q" placeholder="Mã phiếu, độc giả, sách, mã bản sao..." value="<?= e($search) ?>"></label>
  <label><span class="sr-only">Trạng thái</span><select name="status"><option value="">Mọi trạng thái</option><?php foreach (['active' => 'Đang mở', 'borrowed' => 'Chưa quá hạn', 'overdue' => 'Quá hạn', 'returned' => 'Đã trả'] as $key => $label): ?><option value="<?= e($key) ?>"<?= selected($status, $key) ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
  <button class="button button-outline">Lọc phiếu</button>
</form>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">SỔ MƯỢN TRẢ</span><h2>Phiếu mượn</h2></div><span class="muted"><?= count($loans) ?> phiếu</span></div>
  <?php if ($loans): ?>
    <div class="table-scroll"><table>
      <thead><tr><th>Mã phiếu</th><th>Độc giả</th><th>Bản sao và hạn trả</th><th>Ngày mượn</th><th>Trạng thái phiếu</th></tr></thead>
      <tbody>
      <?php foreach ($loans as $loan): ?>
        <tr>
          <td><strong><?= e($loan['loan_code']) ?></strong><small><?= (int) $loan['item_count'] ?> bản sao</small></td>
          <td><?= e($loan['full_name']) ?><small><?= e($loan['card_number']) ?></small></td>
          <td><div class="activity-list">
            <?php foreach ($itemsByLoan[(int) $loan['id']] ?? [] as $item): ?>
              <div class="activity-item"><span class="activity-dot"></span><div>
                <strong><?= e($item['book_title']) ?></strong>
                <small><?= e($item['copy_code']) ?> · Hạn trả <?= e(format_date($item['due_date'])) ?> · <span class="status-pill <?= e($item['loan_status']) ?>"><?= $item['loan_status'] === 'returned' ? 'Đã trả' : ($item['loan_status'] === 'overdue' ? 'Quá hạn' : 'Đang mượn') ?></span><?php if ((float) $item['fine_amount'] > 0): ?> · Phí tạm tính <?= number_format((float) $item['fine_amount'], 0, ',', '.') ?>đ<?php endif; ?></small>
                <?php if ($item['returned_at'] === null): ?>
                  <div class="inline-actions loan-item-actions">
                    <form method="post" action="<?= e(url('/admin/loans/return')) ?>" class="inline-actions" data-confirm="Xác nhận ghi nhận trả bản sao này?">
                      <?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                      <label class="sr-only" for="condition-<?= (int) $item['id'] ?>">Tình trạng bản sao khi nhận trả</label>
                      <select id="condition-<?= (int) $item['id'] ?>" name="condition"><option value="good">Tốt</option><option value="worn">Có hao mòn</option><option value="damaged">Hỏng</option><option value="lost">Báo mất</option></select>
                      <button class="button button-small button-outline">Ghi nhận trả</button>
                    </form>
                    <?php if ($item['loan_status'] !== 'overdue' && (int) ($item['renewal_count'] ?? 0) < $maxRenewals): ?>
                      <form method="post" action="<?= e(url('/admin/loans/renew')) ?>" data-confirm="Gia hạn riêng bản sao này?">
                        <?= csrf_field() ?><input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>"><button class="button button-small button-outline">Gia hạn <?= (int) ($item['renewal_count'] ?? 0) ?>/<?= (int) $maxRenewals ?></button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php else: ?><small>Đã nhận trả <?= e(format_date($item['returned_at'])) ?></small><?php endif; ?>
              </div></div>
            <?php endforeach; ?>
          </div></td>
          <td><?= e(format_date($loan['borrowed_at'])) ?></td>
          <td><span class="status-pill <?= e($loan['loan_status']) ?>"><?= $loan['loan_status'] === 'returned' ? 'Đã hoàn tất' : ($loan['loan_status'] === 'overdue' ? 'Có bản quá hạn' : 'Đang mở') ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else: ?><div class="empty-inline">Không có phiếu mượn phù hợp.</div><?php endif; ?>
</section>
<section class="panel reservation-panel">
  <div class="panel-heading"><div><span class="eyebrow">ĐẶT TRƯỚC SÁCH</span><h2>Yêu cầu chờ xử lý</h2></div><span class="muted"><?= count($reservations) ?> yêu cầu</span></div>
  <?php if ($reservations): ?>
    <div class="table-scroll"><table>
      <thead><tr><th>Độc giả</th><th>Sách và bản giữ</th><th>Ngày yêu cầu</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
      <tbody><?php foreach ($reservations as $reservation): ?>
        <tr>
          <td><?= e($reservation['full_name']) ?><small><?= e($reservation['card_number']) ?></small></td>
          <td><?= e($reservation['title']) ?><?php if (!empty($reservation['copy_code'])): ?><small>Bản giữ: <?= e($reservation['copy_code']) ?> · nhận trước <?= e(date('d/m/Y', strtotime($reservation['expires_at']))) ?></small><?php endif; ?></td>
          <td><?= e(date('d/m/Y', strtotime($reservation['reserved_at']))) ?></td>
          <td><span class="status-pill <?= e($reservation['status']) ?>"><?= $reservation['status'] === 'ready' ? 'Đã sẵn sàng' : 'Đang chờ' ?></span></td>
          <td><div class="inline-actions">
            <?php if ($reservation['status'] === 'pending'): ?><form method="post" action="<?= e(url('/admin/reservations')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><input type="hidden" name="status" value="ready"><button class="icon-button">Đánh dấu sẵn sàng</button></form><?php endif; ?>
            <form method="post" action="<?= e(url('/admin/reservations')) ?>" data-confirm="Hủy yêu cầu đặt trước này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>"><input type="hidden" name="status" value="cancelled"><button class="icon-button danger-button">Hủy</button></form>
          </div></td>
        </tr>
      <?php endforeach; ?></tbody>
    </table></div>
  <?php else: ?><div class="empty-inline">Không có yêu cầu đặt trước đang hoạt động.</div><?php endif; ?>
</section>
<dialog class="form-dialog" id="loanDialog">
  <form method="post" action="<?= e(url('/admin/loans')) ?>" class="form-stack" data-dialog-form>
    <?= csrf_field() ?>
    <div class="dialog-heading"><div><span class="eyebrow">PHIẾU MƯỢN MỚI</span><h2>Lập phiếu mượn nhiều sách</h2></div><button type="button" class="dialog-close" data-dialog-close aria-label="Đóng">×</button></div>
    <label>Độc giả<select name="reader_id" required><option value="">Chọn độc giả</option><?php foreach ($readers as $reader): ?><option value="<?= (int) $reader['id'] ?>"><?= e($reader['card_number'] . ' — ' . $reader['full_name']) ?></option><?php endforeach; ?></select></label>
    <label>Bản sao sách (có sẵn hoặc giữ cho đúng độc giả) <span class="muted">(chọn tối đa <?= (int) $maxBooks ?> bản, giữ Ctrl để chọn nhiều)</span><select name="copy_ids[]" multiple size="8" required><?php foreach ($copies as $copy): ?><option value="<?= (int) $copy['id'] ?>"><?= e($copy['book_code'] . ' — ' . $copy['title'] . ' · ' . $copy['copy_code'] . (!empty($copy['reserved_card']) ? ' · Giữ cho ' . $copy['reserved_card'] : '')) ?></option><?php endforeach; ?></select></label>
    <?php if (!$copies): ?><div class="empty-inline">Hiện không có bản sao sẵn sàng để mượn.</div><?php endif; ?>
    <div class="form-grid"><label>Ngày mượn<input type="date" name="borrowed_at" value="<?= e(date('Y-m-d')) ?>" required></label><label>Hạn trả<input type="date" name="due_at" value="<?= e(date('Y-m-d', strtotime('+' . (int) $defaultLoanDays . ' days'))) ?>" required></label></div>
    <label>Ghi chú<textarea name="note" rows="3" maxlength="500"></textarea></label>
    <div class="dialog-actions"><button type="button" class="button button-outline" data-dialog-close>Hủy</button><button class="button button-primary"<?= !$copies ? ' disabled' : '' ?>>Tạo phiếu</button></div>
  </form>
</dialog>
