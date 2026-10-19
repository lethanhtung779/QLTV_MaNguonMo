<?php
$listFields = '<input type="hidden" name="_list_q" value="' . e($search) . '">'
    . '<input type="hidden" name="_list_status" value="' . e($status) . '">'
    . '<input type="hidden" name="_list_scope" value="' . e($scope) . '">'
    . '<input type="hidden" name="_list_date_from" value="' . e($dateFrom) . '">'
    . '<input type="hidden" name="_list_date_to" value="' . e($dateTo) . '">'
    . '<input type="hidden" name="_list_page" value="' . (int) $pagination->page . '">';

$activeTab = $realOnly ? 'operational' : 'archive';
?>

<!-- Header -->
<div class="page-title-row" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
  <div>
    <span class="eyebrow" style="color: #C58B54; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">THEO DÕI LƯU HÀNH TÀI LIỆU</span>
    <h1 style="color: #193E30; margin: 0.2rem 0; font-size: 1.6rem; font-family: serif;">Quản lý Mượn / Trả</h1>
    <p style="color: #74685c; margin: 0; font-size: 0.9rem;">
      Quản lý chu trình mượn - trả sách thực tế, tiếp nhận từng bản sao vật lý, theo dõi gia hạn và thu hồi tài liệu.
    </p>
  </div>
  <?php if (can('loans.create')): ?>
    <button class="button button-primary" type="button" data-dialog-open="loanDialog" style="padding: 0.65rem 1.25rem; font-size: 0.9rem; font-weight: 600; background: #193E30; border-color: #193E30; box-shadow: 0 2px 6px rgba(25, 62, 48, 0.2); cursor: pointer;">
      ＋ Lập phiếu mượn mới
    </button>
  <?php endif; ?>
</div>

<!-- Real KPI Cards -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Đang mượn</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #193E30; display: block; margin-top: 0.25rem;">
      <?= (int) ($kpis['active_loans'] ?? 0) ?>
    </span>
    <small style="color: #74685c; font-size: 0.75rem;">Phiếu chưa trả hết</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Quá hạn</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #dc2626; display: block; margin-top: 0.25rem;">
      <?= (int) ($kpis['overdue_loans'] ?? 0) ?>
    </span>
    <small style="color: #dc2626; font-size: 0.75rem;">Cần đôn đốc thu hồi</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Đã trả</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #28a745; display: block; margin-top: 0.25rem;">
      <?= (int) ($kpis['returned_loans'] ?? 0) ?>
    </span>
    <small style="color: #28a745; font-size: 0.75rem;">Đã hoàn tất lưu hành</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Yêu cầu chờ xử lý</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #d97706; display: block; margin-top: 0.25rem;">
      <?= (int) ($kpis['pending_reservations'] ?? 0) ?>
    </span>
    <small style="color: #d97706; font-size: 0.75rem;">Đặt trước cần giải quyết</small>
  </div>
</div>

<!-- Mode Tabs: Operational vs Archived Demo Fixtures -->
<div class="loan-mode-tabs" style="display: flex; gap: 0.5rem; border-bottom: 2px solid #E8D8C5; margin-bottom: 1.25rem;">
  <a href="<?= e(url('/admin/loans?scope=operational')) ?>" 
     style="padding: 0.6rem 1.25rem; font-size: 0.88rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid <?= $realOnly ? '#193E30' : 'transparent' ?>; color: <?= $realOnly ? '#193E30' : '#74685c' ?>; margin-bottom: -2px; display: inline-flex; align-items: center; gap: 6px;">
    <span>⚡ Phiếu lưu hành thực tế</span>
    <?php if ($realOnly): ?>
      <span class="status-pill" style="font-size: 0.7rem; background: #193E30; color: #fff; padding: 1px 6px; border-radius: 10px;">Vận hành</span>
    <?php endif; ?>
  </a>

  <a href="<?= e(url('/admin/loans?scope=archive')) ?>" 
     style="padding: 0.6rem 1.25rem; font-size: 0.88rem; font-weight: 600; text-decoration: none; border-bottom: 3px solid <?= !$realOnly ? '#C58B54' : 'transparent' ?>; color: <?= !$realOnly ? '#C58B54' : '#74685c' ?>; margin-bottom: -2px; display: inline-flex; align-items: center; gap: 6px;">
    <span>📁 Lưu trữ / Kiểm toán phiếu mẫu</span>
    <?php if (!$realOnly): ?>
      <span class="status-pill" style="font-size: 0.7rem; background: #C58B54; color: #fff; padding: 1px 6px; border-radius: 10px;">Lưu trữ</span>
    <?php endif; ?>
  </a>
</div>

<?php if (!$realOnly): ?>
  <div style="background: #fffbeb; border-left: 4px solid #C58B54; padding: 10px 14px; margin-bottom: 1.25rem; border-radius: 4px; font-size: 0.85rem; color: #92400e;">
    <strong>Khu vực kiểm toán & dữ liệu lưu trữ:</strong> Đang xem lịch sử phiếu mẫu và dữ liệu kiểm thử cũ. Các phiếu này được bảo toàn nguyên vẹn trong MySQL phục vụ kiểm toán và không ảnh hưởng đến số liệu vận hành thực tế.
  </div>
<?php endif; ?>

<!-- Comprehensive Filter Bar -->
<form class="filter-bar staff-filter" method="get" action="<?= e(url('/admin/loans')) ?>" style="background: #fff; border: 1px solid #E8D8C5; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
  <input type="hidden" name="scope" value="<?= e($scope) ?>">

  <label class="catalog-search" style="flex: 2 1 240px; margin: 0;">
    <input type="search" name="q" placeholder="Tìm theo mã phiếu, tên độc giả, tựa sách, mã bản sao..." value="<?= e($search) ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
  </label>

  <label style="flex: 1 1 160px; margin: 0;">
    <select name="status" style="width: 100%; padding: 0.5rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
      <option value="">Tất cả trạng thái phiếu</option>
      <option value="active" <?= selected($status, 'active') ?>>Đang mở (chưa trả hết)</option>
      <option value="borrowed" <?= selected($status, 'borrowed') ?>>Trong hạn mượn</option>
      <option value="overdue" <?= selected($status, 'overdue') ?>>Có bản quá hạn</option>
      <option value="returned" <?= selected($status, 'returned') ?>>Đã hoàn tất (đã trả hết)</option>
    </select>
  </label>

  <label style="display: flex; align-items: center; gap: 4px; font-size: 0.8rem; color: #74685c; margin: 0;">
    Từ:
    <input type="date" name="date_from" value="<?= e($dateFrom) ?>" style="padding: 0.45rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
  </label>

  <label style="display: flex; align-items: center; gap: 4px; font-size: 0.8rem; color: #74685c; margin: 0;">
    Đến:
    <input type="date" name="date_to" value="<?= e($dateTo) ?>" style="padding: 0.45rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
  </label>

  <button class="button button-outline" type="submit" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: #193E30; color: #193E30;">
    Lọc phiếu
  </button>
  <?php if ($search !== '' || $status !== '' || $dateFrom !== '' || $dateTo !== ''): ?>
    <a href="<?= e(url('/admin/loans?scope=' . e($scope))) ?>" class="button button-quiet" style="font-size: 0.8rem; color: #b91c1c;">Xóa lọc</a>
  <?php endif; ?>
</form>

<!-- Main Loans Table -->
<section class="panel" style="background: #fff; border: 1px solid #E8D8C5; border-radius: 8px; overflow: hidden; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
  <div class="panel-heading" style="padding: 14px 18px; border-bottom: 1px solid #E8D8C5; margin: 0; background: #FAF7F2; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">SỔ LƯU HÀNH TỔNG QUAN</span>
      <h2 style="font-size: 1.15rem; margin: 0; color: #193E30; font-family: serif;">
        <?= $realOnly ? 'Danh sách phiếu mượn vận hành' : 'Lịch sử phiếu lưu trữ / kiểm toán' ?>
      </h2>
    </div>
    <span class="muted" style="font-size: 0.85rem; font-weight: 500; color: #74685c;">
      Hiển thị <strong><?= count($loans) ?></strong> phiếu (Tổng: <?= (int) $pagination->total ?>)
    </span>
  </div>

  <?php if ($loans): ?>
    <div class="table-scroll" style="overflow-x: auto;">
      <table class="data-table premium-loans-table" style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
        <thead>
          <tr style="background: #fdfbf7; border-bottom: 1px solid #E8D8C5;">
            <th style="width: 150px; padding: 12px 14px;">Mã phiếu & Ngày</th>
            <th style="width: 180px; padding: 12px 14px;">Độc giả</th>
            <th style="padding: 12px 14px;">Chi tiết bản sao mượn & Hạn trả</th>
            <th style="width: 130px; text-align: center; padding: 12px 14px;">Trạng thái</th>
            <th style="width: 170px; text-align: right; padding: 12px 14px;">Biên nhận & Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($loans as $loan): ?>
            <?php 
              $loanItems = $itemsByLoan[(int) $loan['id']] ?? [];
              $hasReturned = false;
              $hasActive = false;
              foreach ($loanItems as $li) {
                if ($li['returned_at'] !== null) {
                  $hasReturned = true;
                } else {
                  $hasActive = true;
                }
              }
            ?>
            <tr style="vertical-align: top; border-bottom: 1px solid #eee;">
              <!-- Mã phiếu & Ngày mượn -->
              <td style="padding: 14px;">
                <strong style="color: #193E30; font-family: monospace; font-size: 0.88rem; display: block; margin-bottom: 2px;">
                  <?= e($loan['loan_code']) ?>
                </strong>
                <small style="color: #74685c; display: block; margin-bottom: 4px;">
                  Mượn: <?= e(format_date($loan['borrowed_at'])) ?>
                </small>
                <span class="status-pill" style="font-size: 0.7rem; padding: 1px 6px; border-radius: 4px; background: #f0ece3; color: #5a5246; border: 1px solid #E8D8C5;">
                  <?= (int) $loan['item_count'] ?> bản sao
                </span>
                <?php if (!empty($loan['note']) && str_starts_with($loan['note'], 'DEMO_FIXTURE:')): ?>
                  <span class="status-pill" style="font-size: 0.65rem; padding: 1px 5px; background: #fef3c7; color: #92400e; display: block; margin-top: 4px; border-radius: 3px;">
                    <?= e($loan['note']) ?>
                  </span>
                <?php endif; ?>
              </td>

              <!-- Độc giả -->
              <td style="padding: 14px;">
                <strong style="color: #193E30; display: block; font-size: 0.9rem; line-height: 1.3;">
                  <?= e($loan['full_name']) ?>
                </strong>
                <small style="color: #74685c; font-family: monospace; display: block; margin-top: 2px;">
                  <?= e($loan['card_number']) ?>
                </small>
              </td>

              <!-- Danh sách bản sao mượn -->
              <td style="padding: 14px;">
                <div class="loan-line-list" style="display: flex; flex-direction: column; gap: 8px;">
                  <?php foreach ($loanItems as $item): ?>
                    <div class="loan-line" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 10px; background: #fdfbf7; border: 1px solid #f0ece3; border-radius: 6px; gap: 8px;">
                      
                      <!-- Book Info -->
                      <div class="loan-line-book" style="display: flex; align-items: center; gap: 8px; flex: 1;">
                        <div>
                          <strong style="font-size: 0.85rem; color: #193E30; display: block; line-height: 1.25;">
                            <?= e($item['book_title']) ?>
                          </strong>
                          <small style="color: #74685c; font-size: 0.76rem;">
                            Bản sao: <code style="font-weight: 600; color: #193E30;"><?= e($item['copy_code']) ?></code>
                            · Hạn: <strong><?= e(format_date($item['due_date'])) ?></strong>
                          </small>
                        </div>
                      </div>

                      <!-- Item Status -->
                      <div class="loan-line-status" style="white-space: nowrap;">
                        <?php if ($item['loan_status'] === 'returned'): ?>
                          <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; background: #d4edda; color: #155724; border-radius: 4px; font-weight: 500;">
                            ✓ Đã trả (<?= e(format_date($item['returned_at'])) ?>)
                          </span>
                        <?php elseif ($item['loan_status'] === 'overdue'): ?>
                          <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; background: #fee2e2; color: #dc2626; border-radius: 4px; font-weight: 600;">
                            ⚠ Quá hạn <?php if ((float) $item['fine_amount'] > 0): ?>(+<?= number_format((float) $item['fine_amount'], 0, ',', '.') ?>đ)<?php endif; ?>
                          </span>
                        <?php else: ?>
                          <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; background: #e0f2fe; color: #0369a1; border-radius: 4px; font-weight: 500;">
                            Đang mượn
                          </span>
                        <?php endif; ?>
                      </div>

                      <!-- Item Actions (Chỉ hiển thị khi bản sao chưa trả) -->
                      <?php if ($item['returned_at'] === null): ?>
                        <div class="loan-line-actions" style="display: inline-flex; gap: 4px; align-items: center;">
                          <?php if (can('loans.return')): ?>
                            <form method="post" action="<?= e(url('/admin/loans/return')) ?>" class="loan-action-form" data-confirm="Ghi nhận trả bản sao này vào kho?" style="margin: 0; display: inline-flex; gap: 4px;">
                              <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
                              <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                              <select name="condition" style="padding: 2px 5px; font-size: 0.72rem; min-height: 28px; border-radius: 4px; border: 1px solid #E8D8C5;" title="Tình trạng khi nhận trả">
                                <option value="good">Tốt</option>
                                <option value="worn">Hao mòn</option>
                                <option value="damaged">Hỏng</option>
                                <option value="lost">Mất</option>
                              </select>
                              <button class="button button-small button-outline" style="padding: 2px 8px; font-size: 0.75rem; min-height: 28px; border-color: #193E30; color: #193E30;">
                                Trả sách
                              </button>
                            </form>
                          <?php endif; ?>

                          <?php if ($item['loan_status'] !== 'overdue' && (int) ($item['renewal_count'] ?? 0) < $maxRenewals): ?>
                            <?php if (can('loans.renew')): ?>
                              <form method="post" action="<?= e(url('/admin/loans/renew')) ?>" class="loan-action-form" data-confirm="Gia hạn thêm hạn mượn cho bản sao này?" style="margin: 0;">
                                <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
                                <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                <button class="button button-small button-quiet" style="padding: 2px 7px; font-size: 0.74rem; min-height: 28px; color: #193E30;" title="Gia hạn mượn">
                                  Gia hạn (<?= (int) ($item['renewal_count'] ?? 0) ?>/<?= (int) $maxRenewals ?>)
                                </button>
                              </form>
                            <?php endif; ?>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>

                    </div>
                  <?php endforeach; ?>
                </div>
              </td>

              <!-- Trạng thái tổng thể -->
              <td style="text-align: center; padding: 14px;">
                <?php
                  $isAllReturned = !$hasActive;
                  $hasOverdue = false;
                  foreach ($loanItems as $li) {
                    if ($li['returned_at'] === null && $li['due_date'] < date('Y-m-d')) {
                      $hasOverdue = true;
                      break;
                    }
                  }
                ?>
                <span class="status-pill" style="font-size: 0.75rem; padding: 4px 10px; border-radius: 12px; font-weight: 600; background: <?= $isAllReturned ? '#d4edda' : ($hasOverdue ? '#fee2e2' : '#e0f2fe') ?>; color: <?= $isAllReturned ? '#155724' : ($hasOverdue ? '#dc2626' : '#0369a1') ?>;">
                  <?= $isAllReturned ? 'Đã hoàn tất' : ($hasOverdue ? 'Có bản quá hạn' : 'Đang mở') ?>
                </span>
              </td>

              <!-- Biên nhận PDF & Trả toàn bộ -->
              <td style="text-align: right; padding: 14px;">
                <div class="loan-receipt-actions" style="display: flex; flex-direction: column; gap: 6px; align-items: flex-end;">
                  <div class="loan-receipt-links" style="display: flex; gap: 4px;">
                    <a class="button button-small button-quiet" href="<?= e(url('/receipts/loan/' . $loan['id'])) ?>" target="_blank" title="In / Tải Phiếu mượn PDF" style="font-size: 0.72rem; padding: 3px 6px;">
                      📄 Phiếu mượn
                    </a>
                    <?php if ($hasReturned): ?>
                      <a class="button button-small button-quiet" href="<?= e(url('/receipts/return/' . $loan['id'])) ?>" target="_blank" title="In / Tải Biên nhận trả PDF" style="font-size: 0.72rem; padding: 3px 6px;">
                        📄 Biên nhận trả
                      </a>
                    <?php endif; ?>
                  </div>

                  <!-- Nút Trả toàn bộ: CHỈ HIỂN THỊ KHI CÒN BẢN SAO CHƯA TRẢ -->
                  <?php if (can('loans.return') && $hasActive): ?>
                    <form method="post" action="<?= e(url('/admin/loans/return-all')) ?>" class="loan-action-form" data-confirm="Nhận trả toàn bộ các bản sao còn lại của phiếu này với tình trạng đã chọn?" style="margin: 0; display: inline-flex; gap: 4px; align-items: center;">
                      <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
                      <input type="hidden" name="loan_id" value="<?= (int) $loan['id'] ?>">
                      <select name="condition" style="padding: 2px 4px; font-size: 0.72rem; min-height: 26px; border: 1px solid #E8D8C5; border-radius: 3px;">
                        <option value="good">Tốt</option>
                        <option value="worn">Hao mòn</option>
                        <option value="damaged">Hỏng</option>
                        <option value="lost">Mất</option>
                      </select>
                      <button class="button button-small button-outline" style="padding: 2px 8px; font-size: 0.72rem; min-height: 26px; white-space: nowrap; border-color: #193E30; color: #193E30;">
                        Trả toàn bộ
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Single pagination bar at the bottom of the loans list -->
    <?php if ($pagination->pages > 1): ?>
      <div style="padding: 12px 18px; border-top: 1px solid #E8D8C5; background: #FAF7F2;">
        <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Beautiful Empty State -->
    <div class="empty-state" style="padding: 3.5rem 1.5rem; text-align: center; background: #F8F3EB;">
      <span style="font-size: 2.4rem; color: #193E30; display: block; margin-bottom: 0.5rem;">⇄</span>
      <?php if ($realOnly): ?>
        <h3 style="margin: 0.25rem 0; color: #193E30; font-family: serif; font-size: 1.25rem;">Chưa phát sinh giao dịch lưu hành thực tế nào</h3>
        <p style="color: #74685c; font-size: 0.88rem; max-width: 440px; margin: 0 auto 1.25rem;">
          Toàn bộ tài liệu trong kho đang ở trạng thái sẵn sàng. Hãy bấm "Lập phiếu mượn mới" bên dưới để ghi nhận lượt lưu hành sách đầu tiên cho độc giả.
        </p>
        <?php if (can('loans.create')): ?>
          <button class="button button-primary" type="button" data-dialog-open="loanDialog" style="padding: 0.6rem 1.25rem; font-size: 0.9rem; background: #193E30; border-color: #193E30;">
            ＋ Lập phiếu mượn mới ngay
          </button>
        <?php endif; ?>
      <?php else: ?>
        <h3 style="margin: 0.25rem 0; color: #193E30; font-family: serif; font-size: 1.25rem;">Không có phiếu lưu trữ phù hợp với bộ lọc</h3>
        <p style="color: #74685c; font-size: 0.88rem; max-width: 420px; margin: 0 auto;">
          Thử nới lỏng từ khóa tìm kiếm hoặc bỏ chọn các điều kiện lọc.
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Reservations Panel -->
<section class="panel" style="background: #fff; border: 1px solid #E8D8C5; border-radius: 8px; overflow: hidden; margin-bottom: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
  <div class="panel-heading" style="padding: 14px 18px; border-bottom: 1px solid #E8D8C5; margin: 0; background: #FAF7F2; display: flex; justify-content: space-between; align-items: center;">
    <div>
      <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">ĐẶT TRƯỚC SÁCH (RESERVATIONS)</span>
      <h2 style="font-size: 1.15rem; margin: 0; color: #193E30; font-family: serif;">Yêu cầu giữ chỗ đang chờ xử lý</h2>
    </div>
    <span class="muted" style="font-size: 0.85rem; font-weight: 500; color: #74685c;"><?= count($reservations) ?> yêu cầu</span>
  </div>

  <?php if ($reservations): ?>
    <div class="table-scroll" style="overflow-x: auto;">
      <table class="data-table" style="width: 100%; font-size: 0.85rem; border-collapse: collapse;">
        <thead>
          <tr style="background: #fdfbf7; border-bottom: 1px solid #E8D8C5;">
            <th style="width: 180px; padding: 10px 14px;">Độc giả</th>
            <th style="padding: 10px 14px;">Tác phẩm & Bản giữ</th>
            <th style="width: 130px; padding: 10px 14px;">Ngày yêu cầu</th>
            <th style="width: 120px; text-align: center; padding: 10px 14px;">Trạng thái</th>
            <th style="width: 160px; text-align: right; padding: 10px 14px;">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $reservation): ?>
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding: 12px 14px;">
                <strong style="color: #193E30; display: block;"><?= e($reservation['full_name']) ?></strong>
                <small style="color: #74685c; font-family: monospace;"><?= e($reservation['card_number']) ?></small>
              </td>
              <td style="padding: 12px 14px;">
                <strong style="font-size: 0.88rem; color: #193E30; display: block;"><?= e($reservation['title']) ?></strong>
                <?php if (!empty($reservation['copy_code'])): ?>
                  <small style="color: #74685c; display: block;">Bản giữ: <code><?= e($reservation['copy_code']) ?></code> · Nhận trước: <?= e(date('d/m/Y', strtotime($reservation['expires_at']))) ?></small>
                <?php endif; ?>
              </td>
              <td style="padding: 12px 14px; font-size: 0.8rem; color: #74685c;"><?= e(date('d/m/Y', strtotime($reservation['reserved_at']))) ?></td>
              <td style="text-align: center; padding: 12px 14px;">
                <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; font-weight: 500; background: <?= $reservation['status'] === 'ready' ? '#d4edda' : '#fff3cd' ?>; color: <?= $reservation['status'] === 'ready' ? '#155724' : '#856404' ?>;">
                  <?= $reservation['status'] === 'ready' ? 'Đã sẵn sàng' : 'Đang chờ' ?>
                </span>
              </td>
              <td style="text-align: right; padding: 12px 14px;">
                <div style="display: inline-flex; gap: 6px; justify-content: flex-end;">
                  <?php if ($reservation['status'] === 'pending' && can('reservations.manage')): ?>
                    <form method="post" action="<?= e(url('/admin/reservations')) ?>" style="margin: 0;">
                      <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>">
                      <input type="hidden" name="status" value="ready">
                      <button class="button button-small button-outline" style="padding: 3px 8px; font-size: 0.75rem; border-color: #193E30; color: #193E30;">
                        Sẵn sàng
                      </button>
                    </form>
                  <?php endif; ?>
                  <?php if (can('reservations.manage')): ?>
                    <form method="post" action="<?= e(url('/admin/reservations')) ?>" data-confirm="Hủy yêu cầu đặt trước này?" style="margin: 0;">
                      <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $reservation['id'] ?>">
                      <input type="hidden" name="status" value="cancelled">
                      <button class="button button-small button-quiet" style="padding: 3px 8px; font-size: 0.75rem; color: #b91c1c;">
                        Hủy
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="empty-inline" style="padding: 24px; text-align: center; color: #74685c; font-size: 0.85rem;">
      Không có yêu cầu đặt trước nào đang chờ xử lý.
    </div>
  <?php endif; ?>
</section>

<!-- Dialog Lập Phiếu Mượn -->
<dialog class="form-dialog" id="loanDialog" style="max-width: 620px; width: 95%; border: 1px solid #193E30; border-radius: 8px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
  <form method="post" action="<?= e(url('/admin/loans')) ?>" class="form-stack" data-dialog-form style="display: flex; flex-direction: column; gap: 1rem;">
    <?= csrf_field() ?><?= $listFields ?><?= mutation_field() ?>
    
    <div class="dialog-heading" style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #E8D8C5; padding-bottom: 0.75rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">LƯU HÀNH TÀI LIỆU VẬN HÀNH</span>
        <h2 data-dialog-title style="color: #193E30; margin: 0.2rem 0; font-family: serif; font-size: 1.35rem;">Lập phiếu mượn sách</h2>
      </div>
      <button type="button" class="dialog-close" data-dialog-close aria-label="Đóng" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #74685c;">×</button>
    </div>

    <!-- 1. Độc giả thực tế đang hoạt động -->
    <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
      Độc giả mượn sách (Chỉ độc giả thực tế có thẻ hoạt động) *
      <select name="reader_id" required style="width: 100%; margin-top: 0.35rem; padding: 0.6rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.88rem;">
        <option value="">-- Chọn độc giả theo mã thẻ hoặc họ tên --</option>
        <?php foreach ($readers as $reader): ?>
          <?php
            $isExpired = !empty($reader['expires_on']) && $reader['expires_on'] < date('Y-m-d');
            $activeLoans = (int) ($reader['active_loans'] ?? 0);
          ?>
          <option value="<?= (int) $reader['id'] ?>" <?= $isExpired ? 'disabled' : '' ?>>
            <?= e($reader['card_number'] . ' — ' . $reader['full_name']) ?>
            (Đang mượn: <?= $activeLoans ?>/<?= (int) $maxBooks ?><?= $isExpired ? ' - THẺ HẾT HẠN' : '' ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <!-- Quick Scan / Filter Copy Code input -->
    <div style="background: #FAF7F2; padding: 0.75rem; border-radius: 6px; border: 1px dashed #C58B54;">
      <label style="font-size: 0.8rem; font-weight: 600; color: #193E30; display: block; margin-bottom: 0.25rem;">
        🔍 Quét Barcode hoặc gõ mã bản sao để tìm nhanh:
      </label>
      <input type="text" id="quick-copy-search" placeholder="Quét mã vạch Code 128 hoặc gõ mã bản sao (VD: B001-0001)..." style="width: 100%; padding: 0.45rem 0.65rem; border: 1px solid #E8D8C5; border-radius: 4px; font-size: 0.85rem; font-family: monospace;">
    </div>

    <!-- 2. Bản sao sách thực tế có thể cho mượn -->
    <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
      Chọn bản sao sách mượn (Tối đa <?= (int) $maxBooks ?> bản/lượt) *
      <small style="color: #74685c; display: block; font-weight: normal; margin-top: 2px; margin-bottom: 4px;">
        Giữ phím <strong>Ctrl</strong> (hoặc <strong>Cmd</strong> trên Mac) để chọn nhiều bản sao cùng lúc.
      </small>
      <select name="copy_ids[]" id="loan-copies-select" data-max-copies="<?= (int) $maxBooks ?>" multiple size="7" required style="width: 100%; font-family: monospace; font-size: 0.82rem; padding: 0.4rem; border: 1px solid #E8D8C5; border-radius: 6px;">
        <?php foreach ($copies as $copy): ?>
          <option value="<?= (int) $copy['id'] ?>" data-search="<?= e(strtolower($copy['copy_code'] . ' ' . ($copy['barcode'] ?? '') . ' ' . $copy['title'])) ?>">
            [<?= e($copy['copy_code']) ?>] <?= e($copy['title']) ?> (<?= e($copy['shelf_location'] ?: 'Chưa xếp kệ') ?>)<?= !empty($copy['reserved_card']) ? ' - [Giữ cho ' . e($copy['reserved_card']) . ']' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <?php if (!$copies): ?>
      <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 8px 12px; font-size: 0.82rem; color: #92400e;">
        Hiện không có bản sao nào ở trạng thái sẵn sàng để mượn. (Các bản sao đang ở trạng thái chờ kiểm kê, đang mượn hoặc hỏng sẽ bị chặn tự động).
      </div>
    <?php endif; ?>

    <!-- 3. Ngày mượn và Hạn trả -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Ngày lập phiếu
        <input type="date" name="borrowed_at" value="<?= e(date('Y-m-d')) ?>" required style="width: 100%; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
      </label>
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Hạn trả dự kiến
        <input type="date" name="due_at" value="<?= e(date('Y-m-d', strtotime('+' . (int) $defaultLoanDays . ' days'))) ?>" required style="width: 100%; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
      </label>
    </div>

    <!-- 4. Ghi chú thủ thư -->
    <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
      Ghi chú thủ thư
      <textarea name="note" rows="2" maxlength="500" placeholder="Ghi chú về tình trạng bản sao khi xuất mượn..." style="width: 100%; margin-top: 0.35rem; padding: 0.5rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;"></textarea>
    </label>

    <div class="dialog-actions" style="display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid #E8D8C5; padding-top: 0.75rem; margin-top: 0.5rem;">
      <button type="button" class="button button-outline" data-dialog-close style="border-color: #74685c; color: #74685c;">Hủy</button>
      <button class="button button-primary" <?= !$copies ? 'disabled' : '' ?> style="background: #193E30; border-color: #193E30;">
        Xác nhận lập phiếu mượn ✓
      </button>
    </div>
  </form>
</dialog>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const quickSearch = document.getElementById('quick-copy-search');
  const copiesSelect = document.getElementById('loan-copies-select');

  if (quickSearch && copiesSelect) {
    quickSearch.addEventListener('input', function() {
      const term = this.value.trim().toLowerCase();
      let matchedFirst = false;

      Array.from(copiesSelect.options).forEach(opt => {
        const text = opt.getAttribute('data-search') || opt.textContent.toLowerCase();
        if (term === '' || text.includes(term)) {
          opt.style.display = '';
          if (term !== '' && !matchedFirst) {
            opt.selected = true;
            matchedFirst = true;
          }
        } else {
          opt.style.display = 'none';
        }
      });
    });

    quickSearch.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        // If an option is selected, keep it
      }
    });
  }
});
</script>
