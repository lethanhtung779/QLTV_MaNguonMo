<div class="page-title-row metadata-quality-heading">
  <div>
    <span class="eyebrow">QUẢN TRỊ THƯ VIỆN</span>
    <h1>Kiểm toán Chất lượng Dữ liệu Sách</h1>
    <p>Đối chiếu ISBN, dữ liệu thư mục, ảnh bìa và bản sao vật lý bằng dữ liệu MySQL hiện tại.</p>
  </div>
  <div class="metadata-heading-actions">
    <a class="button button-outline" href="<?= e(url('/admin/books/import')) ?>">＋ Nhập sách hàng loạt</a>
    <a class="button button-primary" href="<?= e(url('/admin/books')) ?>">Quản lý kho sách</a>
  </div>
</div>

<section class="metadata-kpi-grid" aria-label="Chỉ số chất lượng metadata">
  <article class="metadata-kpi-card metadata-score-card <?= e($qualityClass) ?>">
    <div class="metadata-kpi-topline">
      <span class="metadata-kpi-label">Độ hoàn thiện dữ liệu</span>
      <span class="metadata-quality-badge"><?= e($qualityLevel) ?></span>
    </div>
    <strong class="metadata-kpi-value"><?= (int) $completenessScore ?><small>%</small></strong>
    <div class="metadata-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $completenessScore ?>">
      <span style="width: <?= (int) $completenessScore ?>%"></span>
    </div>
    <details class="metadata-formula">
      <summary>Xem công thức có trọng số</summary>
      <ul>
        <?php foreach ($scoreComponents as $component): ?>
          <li><span><?= e($component['label']) ?> (<?= (int) $component['weight'] ?>%)</span><strong><?= (int) round($component['rate'] * 100) ?>%</strong></li>
        <?php endforeach; ?>
      </ul>
      <p>ISBN và bản sao loại trừ tài liệu số được xác minh là không áp dụng.</p>
    </details>
  </article>

  <article class="metadata-kpi-card">
    <span class="metadata-kpi-label">Đầu sách công khai</span>
    <strong class="metadata-kpi-value"><?= (int) $publicBooks ?><small>/<?= (int) $totalBooks ?></small></strong>
    <?php $publicRate = $totalBooks > 0 ? round($publicBooks * 100 / $totalBooks, 1) : 0; ?>
    <p><strong><?= e((string) $publicRate) ?>%</strong> công khai · <?= (int) ($totalBooks - $publicBooks) ?> nội bộ/ẩn</p>
  </article>

  <article class="metadata-kpi-card">
    <span class="metadata-kpi-label">Chất lượng ISBN</span>
    <strong class="metadata-kpi-value"><?= (int) $validIsbn ?><small> hợp lệ</small></strong>
    <div class="metadata-mini-stats">
      <span><?= (int) $missingIsbn ?> thiếu cần xác minh</span>
      <span><?= (int) $notApplicableIsbn ?> không áp dụng</span>
      <span class="<?= $invalidIsbn > 0 ? 'has-warning' : '' ?>"><?= (int) $invalidIsbn ?> không hợp lệ</span>
    </div>
  </article>

  <article class="metadata-kpi-card">
    <span class="metadata-kpi-label">Bản sao vật lý</span>
    <strong class="metadata-kpi-value"><?= (int) $totalCopies ?></strong>
    <div class="metadata-mini-stats">
      <span class="is-available"><?= (int) $availableCopies ?> sẵn sàng</span>
      <span><?= (int) $pendingAuditCopies ?> chờ kiểm kê</span>
      <span class="<?= $otherCopies > 0 ? 'has-warning' : '' ?>"><?= (int) $otherCopies ?> trạng thái khác</span>
    </div>
    <?php if ($otherCopies > 0): ?>
      <p class="metadata-copy-breakdown">
        <?php foreach ($copyStatusCounts as $status => $count): ?>
          <?php if (!in_array($status, ['available', 'pending_audit'], true)): ?><?= e(ui_label($status, 'copy_status')) ?>: <strong><?= (int) $count ?></strong><?php endif; ?>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </article>
</section>

<aside class="metadata-policy-note">
  <strong>Quy tắc kiểm toán ISBN</strong>
  <p>ISBN hợp lệ phải vượt qua checksum ISBN-10 hoặc ISBN-13. Standard Ebooks/Open Access có URL nguồn và giấy phép hợp lệ được xếp “Không áp dụng ISBN”, không tính là lỗi. ISBN chỉ được cập nhật khi Admin ghi rõ nguồn xác minh đúng ấn bản.</p>
</aside>

<?php if (!empty($duplicateIsbns)): ?>
  <div class="metadata-alert" role="alert">
    <strong>Trùng ISBN cần xem xét</strong>
    <span><?= count($duplicateIsbns) ?> mã chuẩn hóa đang xuất hiện ở nhiều đầu sách.</span>
  </div>
<?php endif; ?>

<section class="panel metadata-filter-panel">
  <nav class="metadata-filter-tabs" aria-label="Bộ lọc kiểm toán">
    <?php
      $filters = [
        'all' => ['Tất cả', $totalBooks],
        'missing_isbn' => ['Thiếu ISBN', $missingIsbn],
        'invalid_isbn' => ['ISBN không hợp lệ', $invalidIsbn],
        'not_applicable' => ['Không áp dụng ISBN', $notApplicableIsbn],
        'missing_cover' => ['Bìa trung tính', $missingCover],
        'zero_copies' => ['Chưa có bản sao', $zeroCopies],
        'duplicate_isbn' => ['Trùng ISBN', count($duplicateIsbns)],
      ];
      foreach ($filters as $key => [$label, $count]):
        $query = http_build_query(array_filter(['filter' => $key, 'q' => $search]));
    ?>
      <a href="<?= e(url('/admin/metadata-quality?' . $query)) ?>" class="metadata-filter-tab<?= $filter === $key ? ' is-active' : '' ?>"><?= e($label) ?> <span><?= (int) $count ?></span></a>
    <?php endforeach; ?>
  </nav>
  <form method="get" action="<?= e(url('/admin/metadata-quality')) ?>" class="metadata-search-form">
    <input type="hidden" name="filter" value="<?= e($filter) ?>">
    <label class="sr-only" for="metadata-search">Tìm trong danh sách kiểm toán</label>
    <input id="metadata-search" type="search" name="q" value="<?= e($search) ?>" placeholder="Tên sách, tác giả, ISBN hoặc mã sách">
    <button type="submit" class="button button-primary button-small">Tìm kiếm</button>
    <?php if ($search !== '' || $filter !== 'all'): ?><a href="<?= e(url('/admin/metadata-quality')) ?>" class="button button-quiet button-small">Xóa lọc</a><?php endif; ?>
  </form>
</section>

<section class="panel" id="audit-results">
  <header class="metadata-results-heading">
    <div>
      <h2>Danh sách kiểm toán dữ liệu</h2>
      <p><?= (int) $pagination->total ?> kết quả · ưu tiên sách thiếu nhiều trường metadata</p>
    </div>
    <span>Trang <?= (int) $pagination->page ?>/<?= (int) $pagination->pages ?></span>
  </header>

  <?php if ($books): ?>
    <div class="table-scroll metadata-audit-table-wrap">
      <table class="data-table metadata-audit-table">
        <thead>
          <tr>
            <th>Ưu tiên</th>
            <th>Mã sách</th>
            <th>Thông tin ấn phẩm</th>
            <th>ISBN</th>
            <th>Bìa</th>
            <th>Tồn kho</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($books as $book): ?>
            <tr>
              <td><span class="metadata-issue-count<?= (int) $book['issue_count'] >= 3 ? ' is-high' : '' ?>"><?= (int) $book['issue_count'] ?> lỗi thiếu</span></td>
              <td>
                <strong class="metadata-book-code"><?= e($book['book_code']) ?></strong>
                <?php if (empty($book['is_public'])): ?><span class="metadata-visibility">Nội bộ/ẩn</span><?php endif; ?>
              </td>
              <td>
                <a class="metadata-book-title" href="<?= e(url('/book/' . (int) $book['id'])) ?>" target="_blank" rel="noopener"><?= e($book['title']) ?></a>
                <small>Tác giả: <?= e($book['author']) ?></small>
                <small><?= e($book['publisher'] ?: 'Chưa cập nhật NXB') ?> · <?= e($book['publication_year'] ?: '—') ?> · <?= e($book['category_name'] ?? 'Chưa phân loại') ?></small>
              </td>
              <td class="metadata-isbn-cell">
                <?php if (!empty($book['isbn'])): ?><code><?= e($book['isbn']) ?></code><?php endif; ?>
                <span class="metadata-isbn-status is-<?= e($book['isbn_status']) ?>"><?= e($book['isbn_status_label']) ?></span>
                <?php if ($book['isbn_status'] === 'not_applicable' && !empty($book['alternative_identifier'])): ?>
                  <a class="metadata-source-link" href="<?= e($book['alternative_identifier']) ?>" target="_blank" rel="noopener noreferrer">Mã định danh nguồn ↗</a>
                <?php endif; ?>
                <?php if ($canEditIsbn && in_array($book['isbn_status'], ['missing', 'invalid'], true)): ?>
                  <details class="metadata-isbn-editor">
                    <summary>Cập nhật ISBN</summary>
                    <form method="post" action="<?= e(url('/admin/metadata-quality/isbn')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                      <label>ISBN-10 hoặc ISBN-13<input name="isbn" maxlength="24" inputmode="text" required placeholder="978-..." value="<?= e((string) $book['isbn']) ?>"></label>
                      <label>Nguồn xác minh đúng ấn bản<input name="verification_source" maxlength="500" required placeholder="NXB, thư viện quốc gia hoặc URL nguồn"></label>
                      <button class="button button-primary button-small">Kiểm tra và lưu</button>
                    </form>
                  </details>
                <?php endif; ?>
              </td>
              <td><?= !empty($book['cover_url']) ? '<span class="metadata-state is-ok">Đã có bìa</span>' : '<span class="metadata-state">Bìa trung tính</span>' ?></td>
              <td><strong><?= (int) $book['available_count'] ?>/<?= (int) $book['copies_count'] ?></strong><small>sẵn sàng / tổng</small></td>
              <td>
                <div class="metadata-row-actions">
                  <a class="button button-quiet button-small" href="<?= e(url('/book/' . (int) $book['id'])) ?>" target="_blank" rel="noopener">Xem ↗</a>
                  <a class="button button-outline button-small" href="<?= e(url('/admin/books/' . (int) $book['id'] . '/copies')) ?>">Bản sao</a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
  <?php else: ?>
    <div class="empty-state metadata-empty-state">
      <span>✓</span>
      <h2>Không có bản ghi phù hợp</h2>
      <p>Thử thay đổi từ khóa hoặc chọn một nhóm kiểm toán khác.</p>
      <a class="button button-outline" href="<?= e(url('/admin/metadata-quality')) ?>">Xem toàn bộ đầu sách</a>
    </div>
  <?php endif; ?>
</section>
