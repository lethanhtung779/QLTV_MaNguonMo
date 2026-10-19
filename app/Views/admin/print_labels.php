<?php
$selectedTemplate = $_GET['template'] ?? 'standard';
$isSingleBook = !empty($book);
?>

<div class="panel-header no-print" style="margin-bottom: 1.5rem;">
  <div>
    <?php if ($isSingleBook): ?>
      <a class="back-link" href="<?= e(url('/admin/books/' . (int) $book['id'] . '/copies')) ?>" style="color: #74685c; font-size: 0.85rem; text-decoration: none; display: inline-block; margin-bottom: 0.5rem;">
        ← Quay lại danh sách bản sao của sách
      </a>
      <span class="eyebrow" style="color: #C58B54; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">IN TEM THEO ĐẦU SÁCH</span>
      <h1 style="color: #193E30; margin: 0.2rem 0; font-size: 1.6rem; font-family: serif;"><?= e($book['title']) ?></h1>
      <p style="color: #74685c; margin: 0; font-size: 0.9rem;">Tạo và in tem nhãn mã vạch Code 128 & QR cho <?= count($copies) ?> bản sao vật lý của cuốn sách này.</p>
    <?php else: ?>
      <span class="eyebrow" style="color: #C58B54; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase;">QUẢN LÝ MÃ VẠCH & BẢN SAO VẬT LÝ</span>
      <h1 style="color: #193E30; margin: 0.2rem 0; font-size: 1.6rem; font-family: serif;">In tem nhãn & Mã vạch kho sách</h1>
      <p style="color: #74685c; margin: 0; font-size: 0.9rem;">Tra cứu bản sao thực tế, tạo tem dán gáy sách hoặc nhãn mã vạch Code 128 chuẩn bị cho kiểm kê và lưu hành.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Main Print Workstation -->
<div class="print-workstation no-print" style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 1.5rem; align-items: start; margin-bottom: 2rem;">

  <!-- LEFT COLUMN: Search, Filters & Copy Selection Table -->
  <div class="copies-selector-panel" style="background: #fff; border: 1px solid #E8D8C5; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
    
    <!-- Filter bar -->
    <form method="get" action="<?= e(url('/admin/print-labels')) ?>" class="filter-form" style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem;">
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <input type="search" name="q" placeholder="Tìm tên sách, mã bản sao, barcode, tác giả, ISBN..." value="<?= e($search) ?>" style="flex: 2 1 200px; padding: 0.5rem 0.75rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.88rem;">
        <select name="category_id" style="flex: 1 1 140px; padding: 0.5rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.85rem;">
          <option value="0">Tất cả thể loại</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= (int) $categoryId === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="button button-outline" type="submit" style="padding: 0.5rem 1rem; font-size: 0.85rem; border-color: #193E30; color: #193E30;">Lọc</button>
      </div>

      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <select name="shelf" style="flex: 1 1 140px; padding: 0.45rem 0.6rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
          <option value="">Tất cả vị trí kệ</option>
          <?php foreach ($shelves as $sh): ?>
            <option value="<?= e($sh) ?>" <?= $shelf === $sh ? 'selected' : '' ?>><?= e($sh) ?></option>
          <?php endforeach; ?>
        </select>

        <select name="status" style="flex: 1 1 140px; padding: 0.45rem 0.6rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
          <option value="">Tất cả trạng thái</option>
          <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Sẵn sàng (Available)</option>
          <option value="pending_audit" <?= $status === 'pending_audit' ? 'selected' : '' ?>>Chờ kiểm kê (Pending Audit)</option>
          <option value="on_loan" <?= $status === 'on_loan' ? 'selected' : '' ?>>Đang mượn (On Loan)</option>
          <option value="reserved" <?= $status === 'reserved' ? 'selected' : '' ?>>Đang giữ chỗ (Reserved)</option>
          <option value="damaged" <?= $status === 'damaged' ? 'selected' : '' ?>>Hư hỏng (Damaged)</option>
        </select>
      </div>
    </form>

    <!-- Selection Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.85rem; background: #F8F3EB; border-radius: 6px; margin-bottom: 0.75rem; border: 1px solid #E8D8C5;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <label style="display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 600; color: #193E30; cursor: pointer; margin: 0;">
          <input type="checkbox" id="select-all-copies" style="cursor: pointer; width: 16px; height: 16px;"> Chọn tất cả trang này
        </label>
        <button type="button" id="btn-clear-selection" class="button button-quiet" style="font-size: 0.75rem; padding: 2px 6px; color: #b91c1c;">Bỏ chọn</button>
      </div>
      <div>
        <span class="status-pill" id="selected-counter" style="background: #193E30; color: #fff; font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 600;">
          Đã chọn: <span id="count-number"><?= count($copies) ?></span> / <?= count($copies) ?> bản
        </span>
      </div>
    </div>

    <!-- Copies List Table -->
    <?php if ($copies): ?>
      <div class="table-wrap" style="max-height: 520px; overflow-y: auto; border: 1px solid #eee; border-radius: 6px;">
        <table class="data-table" style="width: 100%; font-size: 0.84rem;">
          <thead style="position: sticky; top: 0; background: #fdfbf7; z-index: 2;">
            <tr>
              <th style="width: 36px; text-align: center;">✓</th>
              <th>Mã bản sao & Barcode</th>
              <th>Tác phẩm</th>
              <th style="width: 110px;">Vị trí kệ</th>
              <th style="width: 90px; text-align: center;">Trạng thái</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($copies as $copy): ?>
              <?php
                $copyCode = (string) $copy['copy_code'];
                $barcode = (string) ($copy['barcode'] ?: $copyCode);
                $title = (string) ($copy['book_title'] ?? ($book['title'] ?? 'Tài liệu thư viện'));
                $author = (string) ($copy['author'] ?? ($book['author'] ?? ''));
                $shelfLoc = (string) ($copy['shelf_location'] ?: 'Chưa xếp kệ');
                $copyStatus = (string) $copy['status'];
                $statusLabels = [
                  'available' => ['Sẵn sàng', '#28a745', '#e8f5e9'],
                  'pending_audit' => ['Chờ kiểm kê', '#d97706', '#fef3c7'],
                  'on_loan' => ['Đang mượn', '#0284c7', '#e0f2fe'],
                  'reserved' => ['Giữ chỗ', '#7c3aed', '#ede9fe'],
                  'damaged' => ['Hư hỏng', '#dc2626', '#fee2e2'],
                  'lost' => ['Mất', '#4b5563', '#f3f4f6'],
                ];
                $statusInfo = $statusLabels[$copyStatus] ?? [$copyStatus, '#555', '#eee'];
              ?>
              <tr class="copy-row" style="cursor: pointer;" data-id="<?= (int) $copy['id'] ?>">
                <td style="text-align: center; vertical-align: middle;">
                  <input type="checkbox" class="copy-checkbox" checked
                         value="<?= (int) $copy['id'] ?>"
                         data-copy-code="<?= e($copyCode) ?>"
                         data-barcode="<?= e($barcode) ?>"
                         data-title="<?= e($title) ?>"
                         data-author="<?= e($author) ?>"
                         data-shelf="<?= e($shelfLoc) ?>"
                         data-status="<?= e($copyStatus) ?>"
                         style="cursor: pointer; width: 15px; height: 15px;">
                </td>
                <td style="vertical-align: middle;">
                  <strong style="font-family: monospace; color: #193E30; display: block; font-size: 0.85rem;"><?= e($copyCode) ?></strong>
                  <small style="color: #74685c; font-family: monospace; font-size: 0.74rem;">Barcode: <?= e($barcode) ?></small>
                </td>
                <td style="vertical-align: middle;">
                  <span style="font-weight: 600; color: #302B25; display: block; line-height: 1.25;"><?= e($title) ?></span>
                  <?php if ($author): ?>
                    <small style="color: #74685c; font-size: 0.74rem;"><?= e($author) ?></small>
                  <?php endif; ?>
                </td>
                <td style="vertical-align: middle;">
                  <span class="status-pill" style="font-size: 0.72rem; padding: 2px 6px; background: #f0ece3; color: #5a5246; border: 1px solid #E8D8C5;">
                    <?= e($shelfLoc) ?>
                  </span>
                </td>
                <td style="text-align: center; vertical-align: middle;">
                  <span class="status-pill" style="font-size: 0.7rem; padding: 2px 6px; color: <?= $statusInfo[1] ?>; background: <?= $statusInfo[2] ?>; border-radius: 4px; font-weight: 500;">
                    <?= e($statusInfo[0]) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if (!empty($pagination) && $pagination->pages > 1): ?>
        <div style="margin-top: 0.75rem;">
          <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state" style="padding: 2.5rem 1rem; text-align: center; background: #F8F3EB; border-radius: 6px;">
        <span style="font-size: 2rem; color: #193E30;">▤</span>
        <h4 style="margin: 0.5rem 0 0.2rem; color: #193E30;">Không tìm thấy bản sao nào</h4>
        <p style="color: #74685c; font-size: 0.85rem; margin: 0;">Thử nới lỏng từ khóa tìm kiếm hoặc chọn thể loại/vị trí kệ khác.</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- RIGHT COLUMN: Preview & Print Settings (Sticky) -->
  <div class="print-preview-panel" style="background: #fff; border: 2px solid #193E30; border-radius: 8px; padding: 1.25rem; position: sticky; top: 1rem; box-shadow: 0 4px 12px rgba(25, 62, 48, 0.08);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E8D8C5; padding-bottom: 0.75rem; margin-bottom: 1rem;">
      <div>
        <span class="eyebrow" style="color: #C58B54; font-size: 0.72rem; font-weight: 600; text-transform: uppercase;">XEM TRƯỚC BỐ CỤC IN</span>
        <h3 style="margin: 0; color: #193E30; font-size: 1.15rem; font-family: serif;">Mẫu tem nhãn A4</h3>
      </div>
      <button type="button" class="button button-primary" id="btn-trigger-print" style="padding: 0.55rem 1.25rem; font-size: 0.9rem; font-weight: 600; background: #193E30; border-color: #193E30;">
        🖨 In ngay (Print)
      </button>
    </div>

    <!-- Template Options -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
      <div>
        <label style="font-size: 0.8rem; font-weight: 600; color: #193E30; display: block; margin-bottom: 0.3rem;">Mẫu tem nhãn</label>
        <select id="select-label-template" style="width: 100%; padding: 0.45rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
          <option value="standard" <?= $selectedTemplate === 'standard' ? 'selected' : '' ?>>Tem chuẩn (Bìa trong + Barcode + QR)</option>
          <option value="spine" <?= $selectedTemplate === 'spine' ? 'selected' : '' ?>>Tem gáy sách (Spine label)</option>
          <option value="barcode-only" <?= $selectedTemplate === 'barcode-only' ? 'selected' : '' ?>>Tem mã vạch Code 128 tinh gọn</option>
        </select>
      </div>

      <div>
        <label style="font-size: 0.8rem; font-weight: 600; color: #193E30; display: block; margin-bottom: 0.3rem;">Bố cục trang in A4</label>
        <select id="select-sheet-layout" style="width: 100%; padding: 0.45rem; border: 1px solid #E8D8C5; border-radius: 6px; font-size: 0.82rem;">
          <option value="cols-2">2 cột / trang A4 (Tem lớn)</option>
          <option value="cols-3">3 cột / trang A4 (Tem vừa)</option>
        </select>
      </div>
    </div>

    <div id="print-alert-no-selection" style="display: none; background: #fffbeb; border-left: 4px solid #f59e0b; padding: 8px 12px; margin-bottom: 0.75rem; font-size: 0.8rem; color: #92400e;">
      ⚠ Chưa có bản sao nào được chọn. Vui lòng tick chọn ít nhất một bản sao ở danh sách bên trái để in.
    </div>

    <!-- Live Preview Container -->
    <div style="border: 1px dashed #C58B54; border-radius: 6px; background: #FAF7F2; padding: 0.75rem; max-height: 440px; overflow-y: auto;">
      <small style="color: #74685c; display: block; margin-bottom: 0.5rem; text-align: center;">Mô phỏng hiển thị trên giấy in:</small>
      <div id="preview-grid" style="display: flex; flex-direction: column; gap: 0.75rem;">
        <!-- Dynamically filled by JavaScript based on selected checkboxes -->
      </div>
    </div>

    <small style="display: block; color: #74685c; font-size: 0.76rem; margin-top: 0.75rem; text-align: center;">
      Chuẩn mã: <strong>Code 128</strong> vector (rõ nét khi in) & <strong>QR Code ISO/IEC 18004</strong>.
    </small>
  </div>
</div>

<!-- ============================================================== -->
<!-- PRINT ONLY CONTAINER: Renders on window.print()                -->
<!-- ============================================================== -->
<div id="printable-label-sheet" class="print-only">
  <!-- Filled dynamically on print by JS or server rendered -->
  <?php foreach ($copies as $copy): ?>
    <?php
      $cCode = (string) $copy['copy_code'];
      $bCode = (string) ($copy['barcode'] ?: $cCode);
      $cTitle = (string) ($copy['book_title'] ?? ($book['title'] ?? 'Tài liệu thư viện'));
      $cAuthor = (string) ($copy['author'] ?? ($book['author'] ?? ''));
      $cShelf = (string) ($copy['shelf_location'] ?: 'Chưa xếp kệ');
    ?>
    <div class="print-label-item" data-id="<?= (int) $copy['id'] ?>" data-print-selected="1">
      <div class="pl-header">THƯ VIỆN PAPER HAVEN</div>
      <div class="pl-title"><?= e($cTitle) ?></div>
      <?php if ($cAuthor): ?>
        <div class="pl-author"><?= e($cAuthor) ?></div>
      <?php endif; ?>
      <div class="pl-codes">
        <div class="pl-barcode">
          <img src="<?= e(url('/barcode/' . rawurlencode($cCode))) ?>" alt="<?= e($bCode) ?>">
          <div class="pl-barcode-text"><?= e($bCode) ?></div>
        </div>
        <div class="pl-qr">
          <img src="<?= e(url('/qr/' . rawurlencode($cCode))) ?>" alt="QR">
        </div>
      </div>
      <div class="pl-footer">
        <span>Bản: <strong><?= e($cCode) ?></strong></span>
        <span>Kệ: <strong><?= e($cShelf) ?></strong></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<style>
/* Base Screen Styles */
.print-only {
  display: none;
}

@media (max-width: 991px) {
  .print-workstation {
    grid-template-columns: 1fr !important;
  }
  .print-preview-panel {
    position: static !important;
  }
}

/* Print CSS */
@media print {
  @page {
    size: A4 portrait;
    margin: 10mm 10mm 10mm 10mm;
  }
  body, html {
    background: #fff !important;
    color: #000 !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .no-print,
  .staff-sidebar,
  .staff-header,
  .app-header,
  .admin-aside,
  header,
  nav,
  footer,
  .site-footer {
    display: none !important;
  }
  .staff-shell,
  .staff-main,
  .container {
    margin: 0 !important;
    padding: 0 !important;
    max-width: 100% !important;
    border: none !important;
    box-shadow: none !important;
  }
  .print-only {
    display: grid !important;
    gap: 8mm !important;
    width: 100% !important;
  }
  .print-only.cols-2 {
    grid-template-columns: repeat(2, 1fr) !important;
  }
  .print-only.cols-3 {
    grid-template-columns: repeat(3, 1fr) !important;
  }

  .print-label-item[data-print-selected="0"] { display: none !important; }
  .print-label-item {
    border: 1px dashed #444 !important;
    border-radius: 4px !important;
    padding: 3mm 4mm !important;
    background: #fff !important;
    page-break-inside: avoid !important;
    break-inside: avoid !important;
    box-sizing: border-box !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
  }

  .pl-header {
    font-size: 7pt !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.08em !important;
    text-align: center !important;
    border-bottom: 0.5pt solid #888 !important;
    padding-bottom: 1mm !important;
    margin-bottom: 1.5mm !important;
    color: #111 !important;
  }
  .pl-title {
    font-size: 8.5pt !important;
    font-weight: 700 !important;
    line-height: 1.15 !important;
    text-align: center !important;
    max-height: 2.3em !important;
    overflow: hidden !important;
    margin-bottom: 1mm !important;
  }
  .pl-author {
    font-size: 7pt !important;
    text-align: center !important;
    color: #444 !important;
    margin-bottom: 1.5mm !important;
  }
  .pl-codes {
    display: flex !important;
    justify-content: space-around !important;
    align-items: center !important;
    gap: 2mm !important;
    margin: 1.5mm 0 !important;
  }
  .pl-barcode {
    flex: 1 !important;
    text-align: center !important;
  }
  .pl-barcode img {
    display: block !important;
    width: 100% !important;
    max-height: 16mm !important;
    object-fit: contain !important;
    margin: 0 auto !important;
  }
  .pl-barcode-text {
    font-family: monospace !important;
    font-size: 6.5pt !important;
    margin-top: 0.5mm !important;
    letter-spacing: 0.05em !important;
  }
  .pl-qr img {
    display: block !important;
    width: 15mm !important;
    height: 15mm !important;
    object-fit: contain !important;
  }
  .pl-footer {
    display: flex !important;
    justify-content: space-between !important;
    font-size: 6.5pt !important;
    border-top: 0.5pt solid #888 !important;
    padding-top: 1mm !important;
    margin-top: 1mm !important;
  }

  /* Spine template print styling */
  .template-spine .print-label-item {
    padding: 2mm !important;
    text-align: center !important;
  }
  .template-spine .pl-codes {
    flex-direction: column !important;
  }
  .template-spine .pl-qr {
    display: none !important;
  }

  /* Barcode only template */
  .template-barcode-only .pl-title,
  .template-barcode-only .pl-author,
  .template-barcode-only .pl-qr {
    display: none !important;
  }
}

/* Interactive Preview Box Styles */
.mini-label-card {
  background: #fff;
  border: 1px solid #193E30;
  border-radius: 6px;
  padding: 8px 10px;
  font-size: 0.75rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.mini-label-header {
  font-size: 0.65rem;
  font-weight: 700;
  color: #193E30;
  letter-spacing: 0.05em;
  text-align: center;
  border-bottom: 1px solid #E8D8C5;
  padding-bottom: 2px;
  margin-bottom: 4px;
}
.mini-label-title {
  font-weight: 700;
  color: #193E30;
  font-size: 0.8rem;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.mini-label-codes {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin: 6px 0;
}
.mini-label-barcode {
  flex: 1;
  text-align: center;
}
.mini-label-barcode img {
  width: 100%;
  max-height: 38px;
  object-fit: contain;
}
.mini-label-qr img {
  width: 36px;
  height: 36px;
}
.mini-label-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.7rem;
  color: #555;
  border-top: 1px solid #E8D8C5;
  padding-top: 3px;
}
</style>

<style>
/* Screen preview renders from the exact server-generated print labels. */
#print-label-status { font-size: .8rem; margin: .5rem 0; color: #193E30; }
#print-label-status[data-error="1"] { color: #b91c1c; font-weight: 600; }
#preview-grid .mini-label-card { overflow: hidden; min-width: 0; }
#preview-grid .pl-header { font-size: .7rem; font-weight: 700; text-align:center; border-bottom:1px solid #E8D8C5; }
#preview-grid .pl-title { font-size: .82rem; font-weight: 700; text-align:center; white-space: normal; overflow-wrap: anywhere; padding:.35rem 0; }
#preview-grid .pl-author { font-size:.7rem; text-align:center; color:#665c52; }
#preview-grid .pl-codes { display:flex; gap:.5rem; align-items:center; justify-content:space-around; margin:.5rem 0; }
#preview-grid .pl-barcode { flex:1; min-width:0; text-align:center; }
#preview-grid .pl-barcode img { display:block; max-width:100%; width:100%; height:48px; object-fit:contain; }
#preview-grid .pl-barcode-text { font: .72rem monospace; overflow-wrap:anywhere; }
#preview-grid .pl-qr img { display:block; width:52px; height:52px; object-fit:contain; }
#preview-grid .pl-footer { display:flex; flex-wrap:wrap; justify-content:space-between; gap:4px; font-size:.7rem; border-top:1px solid #E8D8C5; padding-top:5px; }
#preview-grid .template-spine .pl-qr, #preview-grid .template-barcode-only .pl-qr,
#preview-grid .template-barcode-only .pl-title, #preview-grid .template-barcode-only .pl-author {display:none;}
#preview-grid .template-spine .pl-codes { flex-direction:column; }
#preview-grid .template-spine .pl-barcode img { height:28px; }
@media (max-width: 700px) { .print-workstation {grid-template-columns:1fr !important;} }
@media print { #print-label-status {display:none !important;} }
</style>
<script>
(function () {
  function initializePrintLabels() {
    const checkboxes = Array.from(document.querySelectorAll('.copy-checkbox'));
    const selectAll = document.getElementById('select-all-copies');
    const clear = document.getElementById('btn-clear-selection');
    const counter = document.getElementById('count-number');
    const preview = document.getElementById('preview-grid');
    const printButton = document.getElementById('btn-trigger-print');
    const template = document.getElementById('select-label-template');
    const layout = document.getElementById('select-sheet-layout');
    const noSelection = document.getElementById('print-alert-no-selection');
    const sheet = document.getElementById('printable-label-sheet');
    if (!preview || !sheet || !printButton || !template || !layout) return;
    let status = document.getElementById('print-label-status');
    if (!status) {
      status = document.createElement('div');
      status.id = 'print-label-status';
      preview.parentNode.insertBefore(status, preview);
    }
    const labels = new Map(Array.from(sheet.querySelectorAll('.print-label-item[data-id]'))
      .map(el => [el.dataset.id, el]));
    function message(text, error = false) {
      status.textContent = text;
      status.dataset.error = error ? '1' : '0';
    }
    function refresh() {
      const selected = checkboxes.filter(cb => cb.checked && labels.has(cb.value));
      counter.textContent = String(selected.length);
      if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && selected.length === checkboxes.length;
        selectAll.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
      }
      const selectedIds = new Set(selected.map(cb => cb.value));
      labels.forEach((el, id) => { el.dataset.printSelected = selectedIds.has(id) ? '1' : '0'; });
      checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        if (row) row.style.background = cb.checked ? '#f7faf8' : '';
      });
      sheet.className = 'print-only ' + layout.value + ' template-' + template.value;
      preview.replaceChildren();
      printButton.disabled = selected.length === 0;
      if (noSelection) noSelection.style.display = selected.length ? 'none' : 'block';
      if (!selected.length) {
        message('Hãy chọn ít nhất một bản sao để xem trước và in.');
        return;
      }
      selected.slice(0, 4).forEach(cb => {
        const original = labels.get(cb.value);
        const card = original.cloneNode(true);
        card.className = 'mini-label-card template-' + template.value;
        card.removeAttribute('data-id');
        card.removeAttribute('data-print-selected');
        preview.appendChild(card);
      });
      if (selected.length > 4) {
        const note = document.createElement('small');
        note.style.cssText = 'display:block;text-align:center;color:#74685c;';
        note.textContent = `Đang xem 4/${selected.length} tem; khi in sẽ có đủ ${selected.length} tem.`;
        preview.appendChild(note);
      }
      message(`Đã chọn ${selected.length} bản sao. Mẫu in ${layout.value === 'cols-3' ? '3 cột' : '2 cột'}.`);
    }
    async function imageReady(image) {
      // Hidden print images may be lazy-loaded; request them explicitly before printing.
      image.loading = 'eager';
      if (!image.complete) {
        await new Promise((resolve, reject) => {
          image.addEventListener('load', resolve, {once:true});
          image.addEventListener('error', reject, {once:true});
          setTimeout(() => reject(new Error('Hết thời gian tải mã')), 10000);
        });
      }
      if (!image.naturalWidth || !image.naturalHeight) throw new Error('Ảnh QR/Barcode bị lỗi hoặc không tồn tại');
      if (image.decode) await image.decode();
    }
    checkboxes.forEach(cb => cb.addEventListener('change', refresh));
    document.querySelectorAll('.copy-row').forEach(row => row.addEventListener('click', event => {
      if (event.target.closest('input, a, button, label')) return;
      const cb = row.querySelector('.copy-checkbox');
      if (cb) { cb.checked = !cb.checked; refresh(); }
    }));
    if (selectAll) selectAll.addEventListener('change', () => {
      checkboxes.forEach(cb => { cb.checked = selectAll.checked; });
      refresh();
    });
    if (clear) clear.addEventListener('click', () => {
      checkboxes.forEach(cb => { cb.checked = false; });
      refresh();
    });
    template.addEventListener('change', refresh);
    layout.addEventListener('change', refresh);
    printButton.addEventListener('click', async () => {
      const selectedLabels = Array.from(labels.values()).filter(el => el.dataset.printSelected === '1');
      if (!selectedLabels.length) return;
      printButton.disabled = true;
      message('Đang kiểm tra QR và Barcode trước khi mở hộp thoại in...');
      try {
        const requiredImages = selectedLabels.flatMap(el => {
          if (template.value === 'spine' || template.value === 'barcode-only')
            return Array.from(el.querySelectorAll('.pl-barcode img'));
          return Array.from(el.querySelectorAll('.pl-barcode img, .pl-qr img'));
        });
        await Promise.all(requiredImages.map(imageReady));
        message(`Đã kiểm tra ${requiredImages.length} hình mã. Chuẩn bị in ${selectedLabels.length} tem.`);
        window.print();
      } catch (err) {
        message('Không thể in: ' + (err.message || 'mã chưa tải được') + '. Kiểm tra quyền truy cập /barcode và /qr.', true);
      } finally {
        printButton.disabled = false;
      }
    });
    refresh();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializePrintLabels, {once:true});
  else initializePrintLabels();
})();
</script>
