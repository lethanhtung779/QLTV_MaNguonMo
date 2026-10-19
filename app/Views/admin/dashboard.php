<div class="page-title-row">
  <div>
    <span class="eyebrow">TỔNG QUAN HỆ THỐNG</span>
    <h1>Bảng điều khiển Thư viện</h1>
    <p>Theo dõi chỉ số hoạt động, lưu hành tài liệu, cảnh báo quá hạn và luồng bạn đọc hôm nay.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <a class="button button-small button-outline" href="<?= e(url('/admin/statistics')) ?>">
      ▥ Xem báo cáo thống kê
    </a>
    <?php if (can('loans.create')): ?>
      <a class="button button-small button-primary" href="<?= e(url('/admin/loans')) ?>">
        ＋ Lập phiếu mượn
      </a>
    <?php endif; ?>
  </div>
</div>

<!-- Key Performance Indicators (KPIs) -->
<section class="metrics-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px;">
  <!-- Metric 1: Đầu sách -->
  <article class="metric-card">
    <span class="metric-icon metric-title">▤</span>
    <span class="metric-label">Tổng đầu sách</span>
    <strong><?= number_format((int) ($bookMetrics['title_count'] ?? 0)) ?></strong>
    <span class="metric-note">
      Trong danh mục Catalog Paper Haven
    </span>
  </article>

  <!-- Metric 2: Bản sao kho -->
  <article class="metric-card">
    <span class="metric-icon metric-copy">▧</span>
    <span class="metric-label">Tổng bản sao vật lý</span>
    <strong><?= number_format((int) ($bookMetrics['copy_count'] ?? 0)) ?></strong>
    <span class="metric-note">
      <strong style="color: #2e7d32;"><?= number_format((int) ($bookMetrics['available_count'] ?? 0)) ?></strong> bản sẵn sàng trên kệ
    </span>
  </article>

  <!-- Metric 3: Đang được mượn -->
  <article class="metric-card">
    <span class="metric-icon metric-borrowed">⇄</span>
    <span class="metric-label">Bản sao đang lưu hành</span>
    <strong><?= number_format((int) ($bookMetrics['loaned_count'] ?? 0)) ?></strong>
    <span class="metric-note">
      Đang được độc giả mượn đọc
    </span>
  </article>

  <!-- Metric 4: Phiếu đang mở -->
  <article class="metric-card">
    <span class="metric-icon metric-loans">◷</span>
    <span class="metric-label">Phiếu mượn đang mở</span>
    <strong><?= number_format((int) ($loanMetrics['active_loan_count'] ?? 0)) ?></strong>
    <span class="metric-note">
      <a class="text-link" href="<?= e(url('/admin/loans?status=active')) ?>">Xem danh sách phiếu →</a>
    </span>
  </article>

  <!-- Metric 5: Phiếu quá hạn -->
  <?php $overdueCount = (int) ($loanMetrics['overdue_loan_count'] ?? 0); ?>
  <article class="metric-card" style="<?= $overdueCount > 0 ? 'border-color: #fca5a5; background: #fffdfd;' : '' ?>">
    <span class="metric-icon metric-overdue" style="<?= $overdueCount > 0 ? 'background: #fee2e2; color: #dc2626;' : '' ?>">!</span>
    <span class="metric-label">Phiếu quá hạn</span>
    <strong style="<?= $overdueCount > 0 ? 'color: #dc2626;' : '' ?>"><?= number_format($overdueCount) ?></strong>
    <?php if ($overdueCount > 0): ?>
      <a class="metric-note alert-text" href="<?= e(url('/admin/loans?status=overdue')) ?>" style="color: #dc2626; font-weight: 600;">
        Cần xử lý nhắc trả ngay →
      </a>
    <?php else: ?>
      <span class="metric-note" style="color: #2e7d32;">Không có phiếu quá hạn</span>
    <?php endif; ?>
  </article>

  <!-- Metric 6: Độc giả -->
  <article class="metric-card">
    <span class="metric-icon metric-readers">♙</span>
    <span class="metric-label">Độc giả hoạt động</span>
    <strong><?= number_format((int) ($readerMetrics['reader_count'] ?? 0)) ?></strong>
    <span class="metric-note">
      <a class="text-link" href="<?= e(url('/admin/readers')) ?>">Quản lý bạn đọc →</a>
    </span>
  </article>

  <!-- Metric 7: Dư nợ phí -->
  <?php $fines = (float) ($outstandingFineBalance ?? 0); ?>
  <article class="metric-card">
    <span class="metric-icon metric-copy">₫</span>
    <span class="metric-label">Dư nợ phí phạt</span>
    <strong><?= number_format($fines, 0, ',', '.') ?><small class="currency">đ</small></strong>
    <?php if ($fines > 0): ?>
      <a class="metric-note alert-text" href="<?= e(url('/admin/fines')) ?>">Thu phí & bồi hoàn →</a>
    <?php else: ?>
      <span class="metric-note" style="color: #2e7d32;">Đã thanh toán hết phí</span>
    <?php endif; ?>
  </article>

  <!-- Metric 8: Đặt trước -->
  <article class="metric-card">
    <span class="metric-icon metric-reservations">↗</span>
    <span class="metric-label">Hàng chờ đặt trước</span>
    <strong><?= number_format((int) ($reservations ?? 0)) ?></strong>
    <span class="metric-note">
      <a class="text-link" href="<?= e(url('/admin/loans#reservations')) ?>">Xử lý giữ sách →</a>
    </span>
  </article>
</section>

<!-- Middle Section: Chart & Popular Books -->
<div class="dashboard-columns" style="margin-bottom: 28px;">
  <!-- Biểu đồ nhịp đọc 6 tháng -->
  <section class="panel dashboard-chart-panel" style="margin-bottom: 0;">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">XU HƯỚNG LƯU HÀNH</span>
        <h2>Nhịp mượn 6 tháng gần nhất</h2>
      </div>
      <a class="text-link" href="<?= e(url('/admin/statistics')) ?>">Báo cáo chi tiết <span>→</span></a>
    </div>

    <?php if ($monthly): ?>
      <?php $monthlyMax = max(1, ...array_map(static fn($item) => (int) $item['loan_count'], $monthly)); ?>
      <div class="bar-chart" role="img" aria-label="Biểu đồ lượt mượn sách theo tháng" style="min-height: 180px; align-items: flex-end;">
        <?php foreach ($monthly as $item): ?>
          <?php $count = (int) $item['loan_count']; ?>
          <div class="bar-column">
            <strong style="color: var(--forest); font-size: 0.85rem;"><?= $count ?></strong>
            <span style="height: <?= max(8, round($count * 140 / $monthlyMax)) ?>px; background: linear-gradient(180deg, var(--gold) 0%, var(--forest) 100%); border-radius: 4px 4px 0 0;" title="<?= $count ?> lượt mượn"></span>
            <small style="color: var(--muted); font-size: 0.72rem;"><?= e(date('m/Y', strtotime($item['month_key'] . '-01'))) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-inline" style="padding: 30px; text-align: center;">Chưa có dữ liệu lượt mượn trong 6 tháng qua.</div>
    <?php endif; ?>
  </section>

  <!-- Sách mượn nhiều nhất -->
  <section class="panel" style="margin-bottom: 0;">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">ĐƯỢC QUAN TÂM</span>
        <h2>Sách mượn nhiều nhất</h2>
      </div>
      <a class="text-link" href="<?= e(url('/admin/books')) ?>">Toàn bộ kho <span>→</span></a>
    </div>

    <?php if ($popularBooks): ?>
      <div class="popular-list">
        <?php foreach ($popularBooks as $book): ?>
          <a class="popular-row" href="<?= e(url('/book/' . $book['id'])) ?>" style="padding: 8px 0; border-bottom: 1px solid var(--border);">
            <span class="popular-cover" style="width: 44px; height: 60px; border-radius: 4px; overflow: hidden; border: 1px solid var(--border);">
              <?= book_cover_markup($book, 'S') ?>
            </span>
            <span style="min-width: 0;">
              <strong style="display: block; font-size: 0.88rem; color: var(--forest); line-height: 1.3; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                <?= e($book['title']) ?>
              </strong>
              <small style="color: var(--muted); display: block;"><?= e($book['author']) ?></small>
            </span>
            <span class="availability <?= (int) $book['available_copies'] > 0 ? 'is-available' : 'is-unavailable' ?>" style="font-size: 0.72rem; padding: 2px 7px; border-radius: 10px; white-space: nowrap;">
              <?= (int) $book['available_copies'] > 0 ? 'Còn ' . (int) $book['available_copies'] . ' bản' : 'Hết bản' ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-inline" style="padding: 30px; text-align: center;">Chưa có thống kê sách mượn nhiều.</div>
    <?php endif; ?>
  </section>
</div>

<!-- Lower Section: Recent Loans & Category Breakdown -->
<div class="dashboard-columns">
  <!-- Phiếu mượn mới nhất -->
  <section class="panel">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">LƯU HÀNH GẦN ĐÂY</span>
        <h2>Phiếu mượn mới cập nhật</h2>
      </div>
      <a class="text-link" href="<?= e(url('/admin/loans')) ?>">Tất cả phiếu <span>→</span></a>
    </div>

    <?php if ($recentLoans): ?>
      <div class="table-scroll">
        <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 0.84rem;">
          <thead>
            <tr>
              <th>Độc giả</th>
              <th>Tựa sách</th>
              <th>Hạn trả</th>
              <th style="text-align: center;">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentLoans as $loan): ?>
              <tr>
                <td>
                  <strong style="color: var(--forest); display: block;"><?= e($loan['full_name']) ?></strong>
                  <small style="color: var(--muted); font-family: monospace;"><?= e($loan['loan_code']) ?></small>
                </td>
                <td><?= e($loan['book_title']) ?></td>
                <td><?= e(format_date($loan['due_at'])) ?></td>
                <td style="text-align: center;">
                  <span class="status-pill <?= e($loan['loan_status']) ?>" style="font-size: 0.7rem; padding: 2px 6px;">
                    <?= $loan['loan_status'] === 'returned' ? 'Đã trả' : ($loan['loan_status'] === 'overdue' ? 'Quá hạn' : 'Đang mượn') ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-inline" style="padding: 24px; text-align: center;">Chưa phát sinh phiếu mượn vận hành. Dữ liệu kiểm thử được quản lý riêng.</div>
    <?php endif; ?>
  </section>

  <!-- Cơ cấu thể loại & Hoạt động -->
  <section class="panel">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">PHÂN BỔ DANH MỤC</span>
        <h2>Cơ cấu theo thể loại sách</h2>
      </div>
      <a class="text-link" href="<?= e(url('/admin/categories')) ?>">Chi tiết <span>→</span></a>
    </div>

    <?php if ($categoryStats): ?>
      <div class="category-stat-list">
        <?php 
          $maxTitles = max(1, ...array_map(static fn($item) => (int) $item['title_count'], $categoryStats)); 
          foreach (array_slice($categoryStats, 0, 5) as $category): 
        ?>
          <div style="margin-bottom: 12px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
              <strong style="color: var(--forest);"><?= e($category['name']) ?></strong>
              <span style="color: var(--muted);"><?= (int) $category['title_count'] ?> đầu sách</span>
            </div>
            <div class="progress-track" style="height: 6px; background: #eee8dc; border-radius: 3px; overflow: hidden;">
              <span style="display: block; height: 100%; width: <?= min(100, round((int) $category['title_count'] * 100 / $maxTitles)) ?>%; background: var(--forest); border-radius: 3px;"></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-inline" style="padding: 24px; text-align: center;">Chưa có dữ liệu thể loại sách.</div>
    <?php endif; ?>

    <!-- Recent Activities if available -->
    <?php if (!empty($activities)): ?>
      <div style="margin-top: 20px; padding-top: 16px; border-top: 1px dashed var(--border);">
        <span class="eyebrow" style="display: block; margin-bottom: 8px;">NHẬT KÝ VẬN HÀNH</span>
        <div class="activity-list">
          <?php foreach (array_slice($activities, 0, 3) as $activity): ?>
            <div class="activity-item" style="padding: 6px 0;">
              <span class="activity-dot"></span>
              <div>
                <strong style="font-size: 0.82rem; color: var(--forest); display: block;"><?= e($activity['details'] ?: $activity['action']) ?></strong>
                <small style="color: var(--muted);"><?= e($activity['user_name'] ?: 'Hệ thống') ?> · <?= e(date('d/m H:i', strtotime($activity['created_at']))) ?></small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>
