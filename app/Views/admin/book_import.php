<div class="page-title-row">
  <div>
    <span class="eyebrow">QUẢN TRỊ KHO & DANH MỤC SÁCH</span>
    <h1>Nhập Sách Hàng Loạt An Toàn</h1>
    <p>Nhập danh sách đầu sách qua tệp CSV hoặc dán trực tiếp. Chống trùng lặp, bảo đảm toàn vẹn dữ liệu với Transaction.</p>
  </div>
  <div style="display: flex; gap: 10px;">
    <a class="button button-outline" href="<?= e(url('/admin/books')) ?>">← Danh mục sách</a>
    <a class="button button-quiet" href="<?= e(url('/admin/metadata-quality')) ?>">Chất lượng Metadata</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="demo-notice-banner" style="background: #fef2f2; border-left: 4px solid #ef4444; margin-bottom: 24px;">
    <div>
      <strong style="color: #991b1b;">Đã phát hiện vấn đề cần lưu ý:</strong>
      <ul style="margin: 6px 0 0; padding-left: 20px; color: #b91c1c;">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<?php if (empty($previewItems)): ?>
  <!-- Upload / Input Form -->
  <section class="panel">
    <div class="panel-heading">
      <div>
        <span class="eyebrow">DỮ LIỆU ĐẦU VÀO</span>
        <h2>Thiết lập cấu hình nhập sách</h2>
      </div>
      <span class="muted">Hỗ trợ định dạng CSV, Tab hoặc Dấu phẩy</span>
    </div>

    <form class="form-stack" method="post" action="<?= e(url('/admin/books/import/preview')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="form-grid">
        <label>
          Thể loại mặc định (khi dòng không chỉ định)
          <select name="default_category_id" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= (int) $cat['id'] ?>"><?= e($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <div class="import-safety-note" role="note">
          <strong>Nhập đầu sách vào danh mục — 0 bản sao</strong>
          <p>Thao tác này không tạo tồn kho, mã vạch hay vị trí kệ. Muốn cho mượn, thủ thư cần tiếp nhận và tạo bản sao tại mục quản lý bản sao của từng sách.</p>
        </div>
      </div>

      <div style="margin-top: 1.25rem;">
        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
          Cách 1: Tải lên tệp CSV
        </label>
        <input type="file" name="csv_file" accept=".csv,text/csv">
        <small style="color: var(--muted); display: block; margin-top: 4px;">Thứ tự cột đề xuất: Tựa sách, Tác giả, ISBN, Nhà xuất bản, Năm xuất bản, Thể loại</small>
      </div>

      <div style="margin-top: 1.5rem;">
        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
          Cách 2: Hoặc dán trực tiếp danh sách sách (mỗi dòng một cuốn)
        </label>
        <textarea name="raw_data" rows="7" placeholder="Tựa sách, Tác giả, ISBN, Nhà xuất bản, Năm xuất bản&#10;Tên sách có thật, Tác giả, 9780132350884, Nhà xuất bản, 2008&#10;Sách chưa có ISBN, Tác giả, , , , Văn học" style="font-family: monospace; font-size: 0.88rem;"></textarea>
      </div>

      <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
        <button class="button button-primary" type="submit">Kiểm tra & Xem trước dữ liệu (Preview) →</button>
      </div>
    </form>
  </section>
<?php else: ?>
  <!-- Preview Step -->
  <section class="panel">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h2 style="margin: 0;">Kết quả phân tích xem trước (<?= count($previewItems) ?> dòng)</h2>
        <small style="color: var(--muted);">
          Chế độ: <strong>Chỉ nhập đầu sách vào Catalog (0 bản sao)</strong>. ISBN chỉ được kiểm tra định dạng/checksum, chưa được đối chiếu với nguồn xuất bản. Vui lòng rà soát kỹ trước khi lưu vào CSDL.
        </small>
      </div>
      <div>
        <a class="button button-quiet button-small" href="<?= e(url('/admin/books/import')) ?>">← Hủy & Nhập lại</a>
      </div>
    </div>

    <div class="table-scroll">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Mã dự kiến</th>
            <th>Tựa sách & Tác giả</th>
            <th>Mã ISBN</th>
            <th>NXB & Năm</th>
            <th>Bản sao & Kệ</th>
            <th>Trạng thái kiểm tra</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($previewItems as $item): ?>
            <?php $isDup = in_array($item['status'], ['DUPLICATE_ISBN', 'DUPLICATE_TITLE', 'INVALID'], true); ?>
            <tr style="<?= $isDup ? 'background: #fff5f5;' : '' ?>">
              <td><?= (int)$item['index'] ?></td>
              <td><strong style="color: var(--forest); font-family: monospace;"><?= e($item['book_code']) ?></strong></td>
              <td>
                <div style="font-weight: 600; color: var(--forest);"><?= e($item['title']) ?></div>
                <small style="color: var(--muted);"><?= e($item['author']) ?></small>
              </td>
              <td>
                <span style="font-family: monospace; font-size: 0.85rem;"><?= e($item['isbn'] ?: '—') ?></span>
              </td>
              <td><?= e($item['publisher'] ?: 'Chưa xác minh NXB') ?> (<?= $item['publication_year'] === null ? 'Chưa rõ năm' : (int) $item['publication_year'] ?>)</td>
              <td>
                <?php if ((int)$item['copies_count'] > 0): ?>
                  <span style="font-weight: 600; color: var(--forest);"><?= (int)$item['copies_count'] ?> bản</span> · ⌖ <?= e($item['shelf_location']) ?>
                <?php else: ?>
                  <span style="color: var(--muted); font-style: italic;">0 bản (Chỉ lưu catalog)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($item['status'] === 'VALID'): ?>
                  <span class="status-badge badge-success">✓ Sẵn sàng lưu</span>
                <?php else: ?>
                  <span class="status-badge badge-warning" title="<?= e($item['note']) ?>">▲ <?= e($item['note']) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <form method="post" action="<?= e(url('/admin/books/import/commit')) ?>" style="margin-top: 24px; display: flex; justify-content: flex-end; align-items: center; gap: 14px; flex-wrap: wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="preview_token" value="<?= e($previewToken ?? '') ?>">
      <a class="button button-quiet" href="<?= e(url('/admin/books/import')) ?>">Hủy bỏ</a>
      <?php $readyCount = count(array_filter($previewItems, static fn($item) => ($item['status'] ?? '') === 'VALID')); ?>
      <button class="button button-primary" type="submit" <?= $readyCount === 0 ? 'disabled' : '' ?>>
        Xác nhận nhập <?= $readyCount ?> đầu sách hợp lệ (không tạo bản sao)
      </button>
    </form>
  </section>
<?php endif; ?>
