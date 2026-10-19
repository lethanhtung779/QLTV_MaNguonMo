<div class="panel-header" style="margin-bottom: 1.5rem;">
  <div>
    <span class="eyebrow" style="color: #C58B54; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">QUẢN LÝ KHO SÁCH & KIỂM ĐỊNH</span>
    <h1 style="color: #193E30; margin: 0.2rem 0; font-size: 1.6rem; font-family: serif;">Kiểm kê kho sách</h1>
    <p style="color: #74685c; margin: 0; font-size: 0.9rem;">Tổ chức các đợt kiểm kê đối soát bản sao thực tế với hệ thống, phát hiện sách lệch kệ, hư hỏng hoặc mất mát.</p>
  </div>
</div>

<div class="stats-grid inventory-audits-grid" style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.75rem; align-items: start; margin-bottom: 2rem;">
  <!-- Create new audit session -->
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="section-heading" style="margin-bottom: 1.25rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">THIẾT LẬP ĐỢT MỚI</span>
        <h3 style="margin: 0.2rem 0; color: #193E30; font-size: 1.15rem; font-family: serif;">Bắt đầu đợt kiểm kê</h3>
      </div>
    </div>
    <form action="<?= e(url('/admin/inventory/audits')) ?>" method="post" class="form-stack" style="display: flex; flex-direction: column; gap: 1rem;">
      <?= csrf_field() ?>
      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Tên đợt kiểm kê *
        <input type="text" name="title" required placeholder="VD: Kiểm kê định kỳ Quý 4/2026" style="width: 100%; margin-top: 0.35rem; padding: 0.55rem 0.75rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.88rem;">
      </label>

      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Phạm vi kiểm kê
        <select name="scope" style="width: 100%; margin-top: 0.35rem; padding: 0.55rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
          <option value="all">Toàn bộ kho sách thư viện</option>
          <?php if (!empty($shelves)): ?>
            <optgroup label="Theo vị trí kệ thực tế (MySQL)">
              <?php foreach ($shelves as $sh): ?>
                <option value="shelf:<?= e($sh['shelf_location']) ?>">
                  <?= e($sh['shelf_location']) ?> (<?= (int) $sh['count'] ?> bản sao)
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <?php if (!empty($categories)): ?>
            <optgroup label="Theo thể loại sách">
              <?php foreach ($categories as $cat): ?>
                <option value="cat:<?= (int) $cat['id'] ?>">
                  <?= e($cat['name']) ?> (<?= (int) $cat['count'] ?> bản sao)
                </option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
        <small style="color: #74685c; font-size: 0.76rem; display: block; margin-top: 0.25rem;">
          Dữ liệu phạm vi được liên kết trực tiếp với dữ liệu kệ và thể loại thật trong cơ sở dữ liệu.
        </small>
      </label>

      <label style="font-size: 0.85rem; font-weight: 600; color: #193E30;">
        Ghi chú / Phân công nhân sự
        <textarea name="notes" rows="3" placeholder="Ghi chú nhân sự kiểm kê hoặc hướng dẫn đối chiếu..." style="width: 100%; margin-top: 0.35rem; padding: 0.55rem 0.75rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;"></textarea>
      </label>

      <button class="button button-primary" type="submit" style="padding: 0.65rem 1.25rem; font-size: 0.9rem; font-weight: 600; background: #193E30; border-color: #193E30; cursor: pointer;">
        Bắt đầu đợt kiểm kê <span>→</span>
      </button>
    </form>
  </section>

  <!-- List of audit sessions -->
  <section class="admin-section" style="background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #E8D8C5; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    <div class="section-heading" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">LỊCH SỬ VẬN HÀNH</span>
        <h3 style="margin: 0.2rem 0; color: #193E30; font-size: 1.15rem; font-family: serif;">Các đợt kiểm kê đã tạo (<?= count($audits) ?>)</h3>
      </div>
    </div>
    <?php if ($audits): ?>
      <div class="table-wrap" style="overflow-x: auto;">
        <table class="data-table" style="width: 100%; font-size: 0.84rem;">
          <thead>
            <tr>
              <th style="width: 120px;">Mã đợt</th>
              <th>Tiêu đề & Phạm vi</th>
              <th style="width: 150px;">Tiến độ & Số lượng</th>
              <th style="width: 110px; text-align: center;">Trạng thái</th>
              <th style="width: 100px;">Ngày tạo</th>
              <th style="width: 110px; text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($audits as $audit): ?>
              <?php
                $scopeText = 'Toàn bộ kho';
                if (str_starts_with($audit['scope'], 'shelf:')) {
                  $scopeText = 'Kệ: ' . substr($audit['scope'], 6);
                } elseif (str_starts_with($audit['scope'], 'cat:')) {
                  $scopeText = 'Thể loại #' . substr($audit['scope'], 4);
                }
                $pct = $audit['total_expected'] > 0 ? round(($audit['total_scanned'] / $audit['total_expected']) * 100) : 0;
              ?>
              <tr>
                <td style="vertical-align: middle;">
                  <strong style="color: #193E30; font-family: monospace; font-size: 0.85rem;"><?= e($audit['audit_code']) ?></strong>
                </td>
                <td style="vertical-align: middle;">
                  <strong style="color: #193E30; display: block; font-size: 0.88rem;"><?= e($audit['title']) ?></strong>
                  <div style="margin-top: 2px;">
                    <span class="status-pill" style="font-size: 0.7rem; padding: 1px 6px; background: #f0ece3; color: #5a5246; border: 1px solid #E8D8C5;">
                      <?= e($scopeText) ?>
                    </span>
                    <small style="color: #74685c; font-size: 0.75rem; margin-left: 4px;">Bởi: <?= e($audit['starter_name']) ?></small>
                  </div>
                </td>
                <td style="vertical-align: middle;">
                  <div style="display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 3px;">
                    <span><strong><?= (int) $audit['total_scanned'] ?></strong> / <?= (int) $audit['total_expected'] ?> bản</span>
                    <span style="font-weight: 600; color: #193E30;"><?= $pct ?>%</span>
                  </div>
                  <div style="width: 100%; height: 6px; background: #eee; border-radius: 3px; overflow: hidden;">
                    <div style="width: <?= min(100, $pct) ?>%; height: 100%; background: #28a745; border-radius: 3px;"></div>
                  </div>
                  <?php if ((int) $audit['total_missing'] > 0 && $audit['status'] === 'completed'): ?>
                    <small style="color: #dc3545; display: block; margin-top: 2px; font-weight: 500;">Thiếu: <?= (int) $audit['total_missing'] ?> bản</small>
                  <?php endif; ?>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <?php
                    $statusStyles = [
                      'in_progress' => ['Đang kiểm kê', '#193E30', '#e6f0eb'],
                      'completed' => ['Đã hoàn tất', '#155724', '#d4edda'],
                      'pending_approval' => ['Chờ duyệt', '#856404', '#fff3cd'],
                      'cancelled' => ['Đã hủy', '#721c24', '#f8d7da'],
                    ];
                    $st = $statusStyles[$audit['status']] ?? [$audit['status'], '#555', '#eee'];
                  ?>
                  <span class="status-pill" style="font-size: 0.72rem; padding: 3px 8px; border-radius: 4px; font-weight: 600; color: <?= $st[1] ?>; background: <?= $st[2] ?>;">
                    <?= e($st[0]) ?>
                  </span>
                </td>
                <td style="vertical-align: middle; font-size: 0.8rem; color: #74685c;">
                  <?= e(date('d/m/Y', strtotime($audit['started_at']))) ?>
                </td>
                <td style="text-align: right; vertical-align: middle;">
                  <a class="button button-small <?= $audit['status'] === 'completed' ? 'button-outline' : 'button-primary' ?>" href="<?= e(url('/admin/inventory/audits/' . (int) $audit['id'])) ?>" style="font-size: 0.78rem; padding: 4px 10px; white-space: nowrap;">
                    <?= $audit['status'] === 'completed' ? 'Biên bản ↗' : 'Quét sách →' ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state" style="padding: 3rem 1.5rem; text-align: center; background: #F8F3EB; border-radius: 6px;">
        <span style="font-size: 2.2rem; color: #193E30;">✓</span>
        <h4 style="margin: 0.5rem 0 0.25rem; color: #193E30; font-family: serif; font-size: 1.15rem;">Chưa có đợt kiểm kê nào</h4>
        <p style="color: #74685c; font-size: 0.88rem; max-width: 420px; margin: 0 auto;">Hãy tạo đợt kiểm kê đầu tiên ở cột bên trái để bắt đầu quét đối soát bản sao kho sách bằng máy quét USB, camera hoặc nhập mã thủ công.</p>
      </div>
    <?php endif; ?>
  </section>
</div>

<style>
@media (max-width: 900px) {
  .inventory-audits-grid {
    grid-template-columns: 1fr !important;
  }
}
</style>
