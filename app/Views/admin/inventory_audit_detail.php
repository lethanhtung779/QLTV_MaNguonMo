<?php
$scopeDisplay = 'Toàn bộ kho sách thư viện';
if (str_starts_with($audit['scope'], 'shelf:')) {
    $scopeDisplay = 'Vị trí kệ: ' . substr($audit['scope'], 6);
} elseif (str_starts_with($audit['scope'], 'cat:')) {
    $scopeDisplay = 'Thể loại #' . substr($audit['scope'], 4);
}
?>

<div class="panel-header" style="margin-bottom: 1.5rem;">
  <div>
    <a class="back-link" href="<?= e(url('/admin/inventory/audits')) ?>" style="color: #74685c; font-size: 0.85rem; text-decoration: none; display: inline-block; margin-bottom: 0.5rem;">
      ← Quay lại danh sách đợt kiểm kê
    </a>
    <span class="eyebrow" style="color: #C58B54; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">
      ĐỢT KIỂM KÊ: <?= e($audit['audit_code']) ?> · <?= e($scopeDisplay) ?>
    </span>
    <h1 style="color: #193E30; margin: 0.2rem 0; font-size: 1.6rem; font-family: serif;"><?= e($audit['title']) ?></h1>
    <p style="color: #74685c; margin: 0; font-size: 0.9rem;">
      Người phụ trách: <strong><?= e($audit['starter_name']) ?></strong> · Bắt đầu: <?= e(date('d/m/Y H:i', strtotime($audit['started_at']))) ?> 
      <?= $audit['completed_at'] ? '· Hoàn tất: <strong>' . e(date('d/m/Y H:i', strtotime($audit['completed_at']))) . '</strong>' : '' ?>
      <?php if (!empty($audit['approver_name'])): ?>
        · Duyệt bởi: <strong><?= e($audit['approver_name']) ?></strong>
      <?php endif; ?>
    </p>
  </div>
  <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
    <a class="button button-outline" href="<?= e(url('/admin/inventory/audits/' . (int) $audit['id'] . '/export')) ?>" style="border-color: #193E30; color: #193E30; font-size: 0.85rem;">
      Xuất biên bản CSV ↗
    </a>
    <?php if ($audit['status'] !== 'completed' && can('books.update')): ?>
      <form action="<?= e(url('/admin/inventory/audits/' . (int) $audit['id'] . '/complete')) ?>" method="post" data-confirm="Bạn có chắc chắn muốn hoàn tất và chốt đợt kiểm kê này? Thao tác này sẽ lưu kết quả và khóa đợt kiểm kê.">
        <?= csrf_field() ?>
        <button class="button button-primary" type="submit" style="font-size: 0.85rem; background: #193E30; border-color: #193E30;">
          Hoàn tất & Chốt kiểm kê ✓
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<!-- KPI Cards -->
<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Tổng sách dự kiến</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #193E30; display: block; margin-top: 0.25rem;"><?= (int) $audit['total_expected'] ?></span>
    <small style="color: #74685c; font-size: 0.75rem;">Theo phạm vi đợt</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Đã quét thực tế</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #28a745; display: block; margin-top: 0.25rem;"><?= (int) $audit['total_scanned'] ?></span>
    <small style="color: #74685c; font-size: 0.75rem;">
      <?= $audit['total_expected'] > 0 ? round(($audit['total_scanned'] / $audit['total_expected']) * 100) : 0 ?>% hoàn thành
    </small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Khớp vị trí kệ</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #193E30; display: block; margin-top: 0.25rem;"><?= (int) $audit['total_matched'] ?></span>
    <small style="color: #28a745; font-size: 0.75rem;">Đúng vị trí hệ thống</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Sai lệch vị trí</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #d97706; display: block; margin-top: 0.25rem;"><?= (int) $audit['total_misplaced'] ?></span>
    <small style="color: #d97706; font-size: 0.75rem;">Cần xếp lại kệ</small>
  </div>

  <div class="stat-card" style="background: #fff; padding: 1.15rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <span class="stat-label" style="font-size: 0.75rem; color: #74685c; text-transform: uppercase; font-weight: 600; display: block;">Thiếu / Chưa đối soát</span>
    <span class="stat-value" style="font-size: 1.8rem; font-weight: 700; color: #dc2626; display: block; margin-top: 0.25rem;"><?= (int) $audit['total_missing'] ?></span>
    <small style="color: #dc2626; font-size: 0.75rem;">Cần rà soát hoặc ghi nhận</small>
  </div>
</div>

<?php if ($audit['status'] !== 'completed'): ?>
  <!-- Scan Barcode Form -->
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 2px solid #193E30; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(25, 62, 48, 0.06);">
    <div class="section-heading" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">TIẾP NHẬN QUÉT ĐỐI SOÁT</span>
        <h2 style="margin: 0.2rem 0; color: #193E30; font-size: 1.25rem; font-family: serif;">Quét mã bản sao (Máy đọc USB, Nhập tay hoặc Camera)</h2>
      </div>
      <button type="button" class="button button-outline" id="btn-toggle-camera" style="border-color: #193E30; color: #193E30; font-size: 0.82rem;">
        📷 Bật Camera quét mã
      </button>
    </div>

    <!-- Live Camera Scanner Box -->
    <div id="camera-scanner-panel" style="display: none; background: #F8F3EB; padding: 1rem; border-radius: 8px; border: 1px dashed #193E30; margin-bottom: 1.25rem; text-align: center;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
        <strong style="color: #193E30; font-size: 0.88rem;">Khung ngắm quét mã vạch / QR qua Camera</strong>
        <button type="button" class="button button-small button-quiet" id="btn-close-camera" style="font-size: 0.75rem;">Đóng camera ✕</button>
      </div>
      <div style="position: relative; max-width: 420px; margin: 0 auto; overflow: hidden; border-radius: 6px; background: #000;">
        <video id="camera-stream" playsinline autoplay muted style="width: 100%; height: auto; display: block;"></video>
        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 75%; height: 55%; border: 2px dashed #C58B54; pointer-events: none;"></div>
      </div>
      <p id="camera-feedback" style="margin-top: 0.5rem; color: #74685c; font-size: 0.85rem;">Đang kết nối camera thiết bị...</p>
    </div>

    <form action="<?= e(url('/admin/inventory/audits/' . (int) $audit['id'] . '/scan')) ?>" method="post" class="inventory-scan-form" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
      <?= csrf_field() ?>
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Mã bản sao hoặc Barcode (Quét súng đọc mã tại đây)
        <input type="text" name="code" required autofocus placeholder="VD: B001-0001 hoặc mã vạch" style="width: 100%; margin-top: 0.35rem; font-size: 1.05rem; padding: 0.6rem 0.75rem; border: 1px solid #193E30; border-radius: 6px; font-family: monospace;">
      </label>
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Tình trạng thực tế
        <select name="condition" style="width: 100%; margin-top: 0.35rem; padding: 0.6rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
          <option value="good">Tốt / Bình thường</option>
          <option value="worn">Hao mòn nhẹ</option>
          <option value="damaged">Hư hỏng</option>
          <option value="lost">Mất / Không tìm thấy</option>
        </select>
      </label>
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Vị trí kệ đang quét
        <input type="text" name="location" placeholder="VD: Kệ V1-01" value="<?= str_starts_with($audit['scope'], 'shelf:') ? e(substr($audit['scope'], 6)) : '' ?>" style="width: 100%; margin-top: 0.35rem; padding: 0.6rem 0.75rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
      </label>
      <button class="button button-primary" type="submit" style="padding: 0.65rem 1.5rem; height: fit-content; font-size: 0.9rem; font-weight: 600; background: #193E30; border-color: #193E30; cursor: pointer;">
        Ghi nhận (Enter) <span>✓</span>
      </button>
    </form>
    <small style="color: #74685c; margin-top: 0.5rem; display: block; font-size: 0.8rem;">
      💡 Mẹo: Máy quét USB sẽ tự động kích hoạt Enter sau khi bấm còi. Nếu bản sao đang được cho mượn hoặc nằm ngoài phạm vi, hệ thống sẽ đưa ra thông báo cảnh báo rõ ràng.
    </small>
  </section>
<?php endif; ?>

<!-- Scan Results and Missing Copies -->
<div style="display: grid; grid-template-columns: 1fr; gap: 2rem;">
  <!-- Scanned items -->
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="section-heading" style="margin-bottom: 1rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">KẾT QUẢ ĐÃ ĐỐI SOÁT</span>
        <h3 style="margin: 0.2rem 0; color: #193E30; font-size: 1.15rem; font-family: serif;">Bản sao đã ghi nhận thực tế (<?= count($items) ?>)</h3>
      </div>
    </div>
    <?php if ($items): ?>
      <div class="table-wrap" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; font-size: 0.84rem;">
          <thead>
            <tr>
              <th style="width: 130px;">Mã bản sao</th>
              <th>Tên sách</th>
              <th style="width: 120px;">Vị trí hệ thống</th>
              <th style="width: 120px;">Vị trí quét</th>
              <th style="width: 100px;">Tình trạng</th>
              <th style="width: 100px; text-align: center;">Khớp vị trí</th>
              <th style="width: 130px;">Thời gian quét</th>
              <th style="width: 110px;">Người quét</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td style="vertical-align: middle;">
                  <strong style="font-family: monospace; color: #193E30;"><?= e($item['copy_code']) ?></strong>
                  <?php if (!empty($item['barcode']) && $item['barcode'] !== $item['copy_code']): ?>
                    <small style="color: #74685c; display: block; font-family: monospace; font-size: 0.72rem;"><?= e($item['barcode']) ?></small>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle;">
                  <strong style="color: #302B25; display: block;"><?= e($item['book_title']) ?></strong>
                  <?php if ($item['copy_status'] === 'on_loan'): ?>
                    <span class="status-pill" style="font-size: 0.7rem; padding: 1px 6px; background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd;">
                      ℹ Đang mượn (Lưu hành)
                    </span>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle;"><?= e($item['expected_location'] ?: 'Chưa gán') ?></td>
                <td style="vertical-align: middle;">
                  <strong style="color: <?= $item['is_matched'] ? '#193E30' : '#d97706' ?>;">
                    <?= e($item['scanned_location'] ?: 'Chưa xác định') ?>
                  </strong>
                </td>
                <td style="vertical-align: middle;">
                  <?php
                    $condLabels = ['good'=>'Tốt','worn'=>'Hao mòn','damaged'=>'Hỏng','lost'=>'Mất'];
                    $condName = $condLabels[$item['scanned_condition']] ?? $item['scanned_condition'];
                  ?>
                  <span class="status-pill" style="font-size: 0.72rem; padding: 2px 6px; background: <?= in_array($item['scanned_condition'], ['damaged', 'lost']) ? '#fee2e2' : '#f0ece3' ?>; color: <?= in_array($item['scanned_condition'], ['damaged', 'lost']) ? '#dc2626' : '#5a5246' ?>;">
                    <?= e($condName) ?>
                  </span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <?php if (!empty($item['note']) && str_contains($item['note'], 'Ngoài phạm vi')): ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; font-weight: 600; color: #7c2d12; background: #ffedd5; border: 1px solid #fed7aa; display: inline-block;">
                      ⊘ Ngoài phạm vi
                    </span>
                  <?php elseif ($item['is_matched']): ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; font-weight: 600; color: #155724; background: #d4edda; display: inline-block;">
                      ✓ Đúng kệ
                    </span>
                  <?php else: ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 7px; font-weight: 600; color: #856404; background: #fff3cd; display: inline-block;">
                      ⚠ Lệch kệ
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($item['note'])): ?>
                    <small style="color: #74685c; display: block; font-size: 0.7rem; margin-top: 2px; max-width: 140px; margin-left: auto; margin-right: auto;"><?= e($item['note']) ?></small>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle; font-size: 0.78rem; color: #74685c;">
                  <?= e(date('d/m/Y H:i:s', strtotime($item['scanned_at']))) ?>
                </td>
                <td style="vertical-align: middle; font-size: 0.8rem;"><?= e($item['scanner_name']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p style="color: #74685c; margin: 0; font-size: 0.88rem;">Chưa có bản sao nào được quét trong đợt kiểm kê này. Hãy dùng máy quét hoặc nhập mã ở khung trên.</p>
    <?php endif; ?>
  </section>

  <!-- Missing copies -->
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="section-heading" style="margin-bottom: 1rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">DANH SÁCH CHƯA ĐỐI SOÁT TRÊN KỆ</span>
        <h3 style="margin: 0.2rem 0; color: #193E30; font-size: 1.15rem; font-family: serif;">Bản sao dự kiến chưa tìm thấy hoặc đang lưu hành (<?= count($missing) ?>)</h3>
      </div>
    </div>
    <?php if ($missing): ?>
      <div class="table-wrap" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; font-size: 0.84rem;">
          <thead>
            <tr>
              <th style="width: 130px;">Mã bản sao</th>
              <th>Tác phẩm & Tác giả</th>
              <th style="width: 120px;">Vị trí kệ dự kiến</th>
              <th style="width: 100px;">Tình trạng</th>
              <th style="width: 160px; text-align: center;">Trạng thái hệ thống</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($missing as $m): ?>
              <tr>
                <td style="vertical-align: middle;">
                  <strong style="font-family: monospace; color: <?= $m['status'] === 'on_loan' ? '#0284c7' : '#dc2626' ?>;"><?= e($m['copy_code']) ?></strong>
                  <?php if (!empty($m['barcode']) && $m['barcode'] !== $m['copy_code']): ?>
                    <small style="color: #74685c; display: block; font-family: monospace; font-size: 0.72rem;"><?= e($m['barcode']) ?></small>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle;">
                  <strong style="color: #302B25; display: block;"><?= e($m['book_title']) ?></strong>
                  <?php if (!empty($m['author'])): ?>
                    <small style="color: #74685c; font-size: 0.75rem;"><?= e($m['author']) ?></small>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle;"><?= e($m['shelf_location'] ?: 'Chưa xếp kệ') ?></td>
                <td style="vertical-align: middle;">
                  <?= e(['good'=>'Tốt','worn'=>'Hao mòn','damaged'=>'Hỏng','lost'=>'Mất'][$m['item_condition']] ?? $m['item_condition']) ?>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <?php if ($m['status'] === 'on_loan'): ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; font-weight: 600; color: #0369a1; background: #e0f2fe; border: 1px solid #bae6fd;">
                      📖 Đang mượn (Hợp lệ)
                    </span>
                    <small style="display: block; font-size: 0.7rem; color: #0284c7; margin-top: 2px;">Không tính là mất trên kệ</small>
                  <?php elseif ($m['status'] === 'reserved'): ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; font-weight: 600; color: #6d28d9; background: #ede9fe; border: 1px solid #ddd6fe;">
                      📌 Đang giữ chỗ
                    </span>
                  <?php else: ?>
                    <span class="status-pill" style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; font-weight: 600; color: #b91c1c; background: #fee2e2; border: 1px solid #fecaca;">
                      ⚠ Chưa quét trên kệ
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div style="padding: 2rem; text-align: center; background: #e8f5e9; border-radius: 6px; color: #2e7d32; border: 1px solid #c8e6c9;">
        <span style="font-size: 1.5rem; display: block; margin-bottom: 0.35rem;">✓</span>
        <strong>Toàn bộ bản sao dự kiến đã được quét đối soát đầy đủ! Không có bản sao nào bị thiếu.</strong>
      </div>
    <?php endif; ?>
  </section>
</div>

<style>
@media (max-width: 860px) {
  .inventory-scan-form {
    grid-template-columns: 1fr !important;
  }
}
</style>

<script src="<?= e(asset('js/inventory-scanner.js')) ?>" defer></script>
