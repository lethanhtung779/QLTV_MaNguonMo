<?php
$currentView = (string) ($_GET['view'] ?? 'table');
$queryString = $_SERVER['QUERY_STRING'] ?? '';
parse_str($queryString, $queryParams);

$tableQuery = $queryParams;
$tableQuery['view'] = 'table';
$tableUrl = '?' . http_build_query($tableQuery);

$gridQuery = $queryParams;
$gridQuery['view'] = 'grid';
$gridUrl = '?' . http_build_query($gridQuery);
?>

<div class="page-title-row">
  <div>
    <span class="eyebrow">QUẢN TRỊ KHO TÀI LIỆU</span>
    <h1>Quản lý danh mục sách</h1>
    <p>Theo dõi danh mục, thông số biên mục, phân loại và tình trạng bản sao trên kệ.</p>
  </div>
  <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
    <?php if (can('reports.view')): ?>
      <a class="button button-small button-outline" href="<?= e(url('/admin/statistics/export?kind=books')) ?>">
        ↓ Xuất CSV
      </a>
    <?php endif; ?>
    <a class="button button-small button-outline" href="<?= e(url('/admin/books/import')) ?>">
      ＋ Nhập hàng loạt
    </a>
    <?php if (can('books.create')): ?>
      <button class="button button-small button-primary" type="button" data-dialog-open="bookDialog">
        ＋ Thêm sách mới
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Filter Bar with View Mode Toggle -->
<form class="filter-bar staff-filter" method="get" action="<?= e(url('/admin/books')) ?>" style="margin-bottom: 20px;">
  <input type="hidden" name="view" value="<?= e($currentView) ?>">

  <label class="catalog-search" style="flex: 2 1 240px;">
    <span>⌕</span>
    <input type="search" name="q" placeholder="Tìm tên sách, tác giả, mã, ISBN..." value="<?= e($search) ?>">
  </label>

  <label style="flex: 1 1 160px;">
    <span class="sr-only">Thể loại</span>
    <select name="category">
      <option value="0">Tất cả thể loại</option>
      <?php foreach ($categories as $category): ?>
        <option value="<?= (int) $category['id'] ?>" <?= selected($categoryId, $category['id']) ?>><?= e($category['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <label style="flex: 1 1 140px;">
    <span class="sr-only">Tình trạng</span>
    <select name="availability">
      <option value="">Mọi tình trạng</option>
      <option value="available" <?= selected($availability, 'available') ?>>Còn sách trên kệ</option>
      <option value="unavailable" <?= selected($availability, 'unavailable') ?>>Chưa có sẵn</option>
    </select>
  </label>

  <label style="flex: 1 1 140px;">
    <span class="sr-only">Sắp xếp</span>
    <select name="sort">
      <option value="newest" <?= selected($sort, 'newest') ?>>Mới cập nhật</option>
      <option value="title" <?= selected($sort, 'title') ?>>Tên sách A-Z</option>
      <option value="author" <?= selected($sort, 'author') ?>>Tác giả A-Z</option>
    </select>
  </label>

  <button class="button button-outline" type="submit">Lọc</button>

  <!-- View Switcher -->
  <div class="view-toggle" style="display: flex; gap: 4px; border: 1px solid var(--border); border-radius: 6px; padding: 2px; background: var(--white); margin-left: auto;">
    <a href="<?= e($tableUrl) ?>" class="button button-small <?= $currentView === 'table' ? 'button-primary' : 'button-quiet' ?>" title="Dạng bảng chi tiết" style="padding: 4px 10px; font-size: 0.78rem;">
      ▤ Bảng
    </a>
    <a href="<?= e($gridUrl) ?>" class="button button-small <?= $currentView === 'grid' ? 'button-primary' : 'button-quiet' ?>" title="Dạng thẻ lưới" style="padding: 4px 10px; font-size: 0.78rem;">
      ▦ Thẻ
    </a>
  </div>
</form>

<?php if ($books): ?>
  <?php if ($currentView === 'grid'): ?>
    <!-- GRID VIEW -->
    <div class="book-grid admin-book-grid">
      <?php foreach ($books as $book): ?>
        <article class="book-card admin-book-card">
          <a class="book-cover-wrap" href="<?= e(url('/book/' . $book['id'])) ?>">
            <?= book_cover_markup($book) ?>
            <span class="book-code"><?= e($book['book_code']) ?><?= empty($book['is_public']) ? ' · Không công khai' : '' ?></span>
          </a>
          <div class="book-card-copy">
            <span class="eyebrow"><?= e($book['category_name'] ?? 'Chưa phân loại') ?></span>
            <h3><a href="<?= e(url('/book/' . $book['id'])) ?>"><?= e($book['title']) ?></a></h3>
            <p><?= e($book['author']) ?></p>
            <div class="book-card-bottom">
              <span class="availability <?= (int) $book['available_copies'] > 0 ? 'is-available' : 'is-unavailable' ?>">
                <?= (int) $book['available_copies'] ?> / <?= (int) $book['total_copies'] ?> bản
              </span>
              <div class="inline-actions">
                <a class="icon-button" href="<?= e(url('/book/' . $book['id'])) ?>">Xem</a>
                <a class="icon-button" href="<?= e(url('/admin/books/' . $book['id'] . '/copies')) ?>">Bản sao</a>
                <?php if (can('books.update')): ?>
                  <button class="icon-button" type="button" aria-label="Sửa sách" data-dialog-open="bookDialog" data-book='<?= e(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>'>Sửa</button>
                <?php endif; ?>
                <?php if (can('books.deactivate')): ?>
                  <form action="<?= e(url('/admin/books/delete')) ?>" method="post" data-confirm="Ngừng lưu hành sách này? Lịch sử mượn sẽ được giữ nguyên.">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                    <button class="icon-button danger-button" aria-label="Ngừng lưu hành sách">Ngừng</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <!-- TABLE VIEW (Compact Enterprise Grid) -->
    <section class="panel" style="padding: 0; overflow: hidden;">
      <div class="table-scroll">
        <table class="data-table book-table" style="width: 100%; border-collapse: collapse;">
          <thead>
            <tr>
              <th style="width: 52px; text-align: center;">Bìa</th>
              <th style="width: 110px;">Mã sách</th>
              <th>Tựa sách & Tác giả</th>
              <th style="width: 130px;">Thể loại</th>
              <th style="width: 135px;">Mã ISBN</th>
              <th style="width: 115px; text-align: center;">Bản sao</th>
              <th style="width: 95px; text-align: center;">Hiển thị</th>
              <th style="width: 190px; text-align: right;">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($books as $book): ?>
              <tr>
                <td style="text-align: center; padding: 8px;">
                  <a href="<?= e(url('/book/' . $book['id'])) ?>" style="display: inline-block; width: 40px; height: 56px; border-radius: 4px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border: 1px solid var(--border);">
                    <?= book_cover_markup($book, 'S') ?>
                  </a>
                </td>
                <td>
                  <strong style="font-family: monospace; font-size: 0.84rem; color: var(--forest);"><?= e($book['book_code']) ?></strong>
                </td>
                <td>
                  <div style="font-weight: 600; font-size: 0.92rem; color: var(--forest); line-height: 1.35;">
                    <a href="<?= e(url('/book/' . $book['id'])) ?>" style="color: inherit; text-decoration: none;"><?= e($book['title']) ?></a>
                  </div>
                  <small style="color: var(--muted);"><?= e($book['author']) ?> <?php if (!empty($book['publication_year'])): ?>· <?= (int) $book['publication_year'] ?><?php endif; ?></small>
                </td>
                <td>
                  <span class="status-pill" style="background: #f0f4f0; color: #2e624c; font-size: 0.72rem; border: 1px solid #d4e3d8; padding: 2px 8px; border-radius: 12px; white-space: nowrap;">
                    <?= e($book['category_name'] ?? 'Chưa phân loại') ?>
                  </span>
                </td>
                <td>
                  <span style="font-family: monospace; font-size: 0.8rem; color: #5a5246;"><?= e($book['isbn'] ?: '—') ?></span>
                </td>
                <td style="text-align: center;">
                  <span class="status-pill <?= (int) $book['available_copies'] > 0 ? 'is-available' : 'is-unavailable' ?>" style="font-size: 0.75rem; padding: 3px 8px; border-radius: 10px; font-weight: 600;">
                    <?= (int) $book['available_copies'] ?> / <?= (int) $book['total_copies'] ?>
                  </span>
                </td>
                <td style="text-align: center;">
                  <?php if (!empty($book['is_public'])): ?>
                    <span style="color: #2e7d32; font-size: 0.75rem; font-weight: 600;">Công khai</span>
                  <?php else: ?>
                    <span style="color: #92400e; font-size: 0.75rem; font-weight: 600;">Nội bộ</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right; padding: 8px 12px;">
                  <div style="display: inline-flex; gap: 4px; justify-content: flex-end;">
                    <a class="button button-small button-outline" href="<?= e(url('/book/' . $book['id'])) ?>" title="Xem trang độc giả" style="padding: 4px 8px; font-size: 0.75rem;">
                      Xem
                    </a>
                    <a class="button button-small button-outline" href="<?= e(url('/admin/books/' . $book['id'] . '/copies')) ?>" title="Quản lý bản sao vật lý" style="padding: 4px 8px; font-size: 0.75rem;">
                      Bản sao
                    </a>
                    <?php if (can('books.update')): ?>
                      <button class="button button-small button-quiet" type="button" aria-label="Sửa sách" data-dialog-open="bookDialog" data-book='<?= e(json_encode($book, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>' style="padding: 4px 8px; font-size: 0.75rem;">
                        Sửa
                      </button>
                    <?php endif; ?>
                    <?php if (can('books.deactivate')): ?>
                      <form action="<?= e(url('/admin/books/delete')) ?>" method="post" data-confirm="Ngừng lưu hành sách này? Lịch sử mượn sẽ được giữ nguyên." style="display: inline; margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                        <button class="button button-small button-quiet" style="padding: 4px 6px; font-size: 0.75rem; color: #b91c1c;" title="Ngừng lưu hành">
                          ✕
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
    </section>
  <?php endif; ?>
<?php else: ?>
  <div class="empty-state">
    <span>▤</span>
    <h2>Chưa có sách phù hợp</h2>
    <p>Thêm tựa sách đầu tiên hoặc điều chỉnh bộ lọc.</p>
    <?php if (can('books.create')): ?>
      <button class="button button-primary" type="button" data-dialog-open="bookDialog">＋ Thêm sách mới</button>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- Dialog Thêm / Sửa Sách -->
<dialog class="form-dialog" id="bookDialog">
  <form method="post" action="<?= e(url('/admin/books')) ?>" class="form-stack" enctype="multipart/form-data" data-dialog-form>
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <input type="hidden" name="return_to" value="<?= e('/admin/books' . (($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '')) ?>">
    
    <div class="dialog-heading">
      <div>
        <span class="eyebrow">DANH MỤC SÁCH</span>
        <h2 data-dialog-title>Thêm đầu sách</h2>
      </div>
      <button type="button" class="dialog-close" data-dialog-close aria-label="Đóng">×</button>
    </div>

    <div class="form-grid">
      <label>
        Mã sách (Ví dụ: B001, AUTH-001)
        <input name="book_code" maxlength="40" required placeholder="B001">
      </label>

      <label>
        Tên sách
        <input name="title" maxlength="240" required placeholder="Nhập nhan đề tác phẩm">
      </label>

      <label>
        Tác giả (ngăn cách bằng dấu chấm phẩy nếu nhiều tác giả)
        <input name="author" maxlength="190" placeholder="Ví dụ: Tác giả A; Tác giả B" required>
      </label>

      <label>
        Mã tiêu chuẩn ISBN
        <input name="isbn" maxlength="20" placeholder="978-604-...">
      </label>

      <label>
        Thể loại
        <select name="category_id">
          <option value="0">Chưa phân loại</option>
          <?php foreach ($categories as $category): ?>
            <option value="<?= (int) $category['id'] ?>"><?= e($category['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>
        Nhà xuất bản
        <input name="publisher" maxlength="190" placeholder="NXB Kim Đồng, NXB Trẻ...">
      </label>

      <label>
        Năm xuất bản
        <input type="number" name="publication_year" min="1" max="<?= (int) date('Y') + 1 ?>" placeholder="<?= date('Y') ?>">
      </label>

      <label>
        Tổng số bản sao (Khởi tạo ban đầu)
        <input type="number" name="total_copies" min="0" max="1000" value="0" required>
        <small style="color: var(--muted);">Nhập 0 nếu chỉ lưu thông tin danh mục, chưa nhập bản sao vật lý.</small>
      </label>

      <label class="form-full">
        Ảnh bìa sách (JPG, PNG, WebP · tối đa 5 MB)
        <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
        <small style="color: var(--muted);">Để trống nếu muốn giữ nguyên bìa hiện tại khi chỉnh sửa.</small>
      </label>

      <label class="form-full">
        Giới thiệu & Tóm tắt tác phẩm
        <textarea name="description" rows="4" maxlength="5000" placeholder="Tóm tắt nội dung tác phẩm..."></textarea>
      </label>
    </div>

    <div class="dialog-actions">
      <button type="button" class="button button-outline" data-dialog-close>Hủy</button>
      <button class="button button-primary">Lưu sách</button>
    </div>
  </form>
</dialog>

<?php include dirname(__DIR__) . '/partials/pagination.php'; ?>
