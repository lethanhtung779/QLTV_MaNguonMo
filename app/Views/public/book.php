<?php
declare(strict_types=1);

$user = current_user();
$isReader = ($user['role'] ?? '') === 'reader';
$meta = book_extended_metadata($book);
$availableCopies = (int) ($book['available_copies'] ?? 0);
$totalCopies = (int) ($book['total_copies'] ?? 0);
$avgRating = (float) ($reviewSummary['average'] ?? 0);
$totalReviews = (int) ($reviewSummary['total'] ?? 0);
?>
<section class="book-v9-page">
  <!-- Breadcrumb -->
  <nav class="book-v9-breadcrumb" aria-label="Đường dẫn trang">
    <a href="<?= e(url('/')) ?>">Trang chủ</a>
    <span>/</span>
    <a href="<?= e(url('/catalog')) ?>">Danh mục sách</a>
    <span>/</span>
    <a href="<?= e(url('/catalog?category=' . (int) ($book['category_id'] ?? 0))) ?>"><?= e($book['category_name'] ?? 'Chung') ?></a>
    <span>/</span>
    <span aria-current="page"><?= e($book['title']) ?></span>
  </nav>

  <!-- Hero Section -->
  <div class="book-v9-hero">
    <!-- Left Column: Cover & Quick Actions -->
    <div class="book-v9-sidebar">
      <div class="book-v9-cover-box">
        <?= book_cover_markup($book, 'L') ?>
      </div>
      <?php if (!book_cover($book, 'L')): ?>
        <div class="unverified-cover-notice">
          <span>ℹ</span> Thư viện sử dụng bìa lưu chiểu trung tính (Chưa có ảnh bìa được xác minh chính thức)
        </div>
      <?php endif; ?>

      <div class="book-v9-sidebar-actions">
        <?php if ($isReader): ?>
          <form action="<?= e(url('/book/' . (int) $book['id'] . '/favorite')) ?>" method="post">
            <?= csrf_field() ?>
            <button class="button <?= !empty($isFavorite) ? 'button-outline' : 'button-quiet' ?> button-wide" type="submit" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
              <span style="font-size: 1.15rem; color: <?= !empty($isFavorite) ? '#c58b54' : 'inherit' ?>;"><?= !empty($isFavorite) ? '♥' : '♡' ?></span>
              <span><?= !empty($isFavorite) ? 'Đã lưu vào Tủ sách' : 'Lưu vào Tủ sách yêu thích' ?></span>
            </button>
          </form>
        <?php endif; ?>

        <?php if (!empty($copies)): ?>
          <a class="button button-outline button-wide" href="#copies" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
            <span>⌖</span>
            <span>Vị trí kệ sách (<?= count($copies) ?> bản)</span>
          </a>
        <?php endif; ?>

        <?php $citation = sprintf('%s (%s). %s. %s.', $meta['author'] ?? $book['author'], $meta['publication_year'] ?? $book['publication_year'] ?? date('Y'), $book['title'], $meta['publisher'] ?? $book['publisher'] ?? 'Paper Haven'); ?>
        <button class="button button-quiet button-wide" type="button" data-copy-citation="<?= e($citation) ?>" id="btnCitation" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%;">
          <span>✎</span>
          <span id="citationText">Sao chép trích dẫn APA</span>
        </button>
      </div>
    </div>

    <!-- Right Column: Bibliographic Header & Quick Specs -->
    <div class="book-v9-main">
      <!-- Badge Row -->
      <div class="book-badge-row">
        <span class="badge-v9 cat"><?= e($book['category_name'] ?? 'Sách thư viện') ?></span>
        <?php if (!empty($meta['ddc'])): ?>
          <span class="badge-v9 ddc" title="Phân loại thập phân Dewey">DDC: <?= e($meta['ddc']) ?></span>
        <?php endif; ?>

        <?php 
          $vStatus = $meta['verification_status'] ?? '';
          $vBadge = $meta['verification_badge'] ?? ($vStatus === 'VERIFIED' ? 'Đã xác minh nguồn gốc thư mục' : 'Chưa đối chiếu thư mục');
        ?>
        <?php if ($vStatus === 'VERIFIED'): ?>
          <span class="badge-v9 verified" title="<?= e($vBadge) ?>">✓ <?= e($vBadge) ?></span>
        <?php elseif ($vStatus === 'PARTIALLY_VERIFIED'): ?>
          <span class="badge-v9 partially-verified" title="<?= e($vBadge) ?>">✓ <?= e($vBadge) ?></span>
        <?php elseif ($vStatus === 'DEMO_DATA_SUSPECTED'): ?>
          <span class="badge-v9 demo-suspected" title="<?= e($vBadge) ?>">▲ <?= e($vBadge) ?></span>
        <?php endif; ?>
      </div>

      <!-- Main Title -->
      <h1 class="book-v9-title"><?= e($book['title']) ?></h1>

      <!-- Original Title if available -->
      <?php if (!empty($meta['original_title']) && $meta['original_title'] !== $book['title']): ?>
        <p class="book-v9-orig-title">Nguyên tác: <strong><?= e($meta['original_title']) ?></strong></p>
      <?php endif; ?>

      <!-- Contributors (Author, Translator) -->
      <div class="book-v9-contributors">
        <span>Tác giả: <strong><?= e($book['author']) ?></strong></span>
        <?php 
          $hasTranslator = !empty($meta['translator']) 
            && !in_array($meta['translator'], ['Không có thông tin dịch giả', 'Không áp dụng', 'Chưa có'], true)
            && !str_starts_with($meta['translator'], 'Nguyên tác')
            && !str_starts_with($meta['translator'], 'Không có');
        ?>
        <?php if ($hasTranslator): ?>
          <span>·</span>
          <span>Dịch giả: <strong><?= e($meta['translator']) ?></strong></span>
        <?php endif; ?>
        <?php if (!empty($meta['edition'])): ?>
          <span>·</span>
          <span>Ấn bản: <strong><?= e($meta['edition']) ?></strong></span>
        <?php endif; ?>
      </div>

      <!-- Metrics Bar -->
      <div class="book-v9-metrics">
        <div class="book-v9-metric">
          <span class="star-val">★ <?= number_format($avgRating, 1) ?></span>
          <span>(<?= $totalReviews ?> đánh giá)</span>
        </div>
        <?php
          $hasPendingAudit = false;
          if (!empty($copies)) {
            foreach ($copies as $cp) {
              if (($cp['status'] ?? '') === 'pending_audit') {
                $hasPendingAudit = true;
                break;
              }
            }
          }
        ?>
        <div class="book-v9-metric">
          <?php if ($totalCopies === 0): ?>
            <span style="color: var(--muted); font-weight: 600;">
              ● Chưa có bản sao để mượn
            </span>
          <?php elseif ($availableCopies > 0): ?>
            <span style="color: #2e7d32; font-weight: 600;">
              ● Hiện còn <?= $availableCopies ?> / <?= $totalCopies ?> bản có thể mượn
            </span>
          <?php elseif ($hasPendingAudit): ?>
            <span style="color: #c58b54; font-weight: 600;">
              ● <?= $totalCopies ?> bản sao đang chờ kiểm kê lưu kho (Chưa mở mượn)
            </span>
          <?php else: ?>
            <span style="color: #a13b30; font-weight: 600;">
              ● Hiện không có bản sao sẵn sàng cho mượn
            </span>
          <?php endif; ?>
        </div>
        <div class="book-v9-metric">
          <?php if ($totalCopies === 0): ?>
            <span>Chính sách: <strong>Mượn tối đa 14 ngày/lượt (khi có bản sao)</strong></span>
          <?php else: ?>
            <span>Thời hạn mượn: <strong>14 ngày</strong></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Reservation Callout if copies exist but none available -->
      <?php if ($totalCopies > 0 && $availableCopies < 1): ?>
        <div class="book-v9-quick-reserve" style="<?= $hasPendingAudit ? 'border-left-color: #c58b54;' : '' ?>">
          <?php if ($hasPendingAudit): ?>
            <h3 style="color: var(--forest);">Bản sao đang trong quy trình kiểm kê tiếp nhận</h3>
            <p>Tài liệu này đã được lập danh mục nhưng các bản sao vật lý đang chờ kiểm kê và dán tem nhãn gáy sách. Bạn đọc có thể đặt trước để nhận thông báo ngay khi sách lên kệ.</p>
          <?php else: ?>
            <h3>Chưa có bản sao sẵn sàng cho mượn</h3>
            <p>Bản sao có thể đang được mượn, hư hỏng hoặc tạm ngừng lưu hành. Vui lòng xem tình trạng từng bản trước khi đặt trước.</p>
          <?php endif; ?>
          <?php if ($isReader): ?>
            <form action="<?= e(url('/account/reservations')) ?>" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
              <button class="button button-primary" type="submit"><?= $hasPendingAudit ? 'Đặt trước khi sách lên giá' : 'Xác nhận đặt trước cuốn sách này' ?> <span>→</span></button>
            </form>
          <?php elseif (!$user): ?>
            <a class="button button-primary" href="<?= e(url('/login')) ?>">Đăng nhập để đặt trước sách <span>→</span></a>
          <?php endif; ?>
        </div>
      <?php elseif ($totalCopies === 0): ?>
        <div class="book-v9-quick-reserve" style="background: #faf8f5; border-left: 4px solid var(--gold);">
          <h3 style="color: var(--forest); font-size: 1.05rem; margin-top: 0;">Hiện chưa có bản sao vật lý tại kệ</h3>
          <p style="margin-bottom: 0; color: var(--muted); font-size: 0.88rem; line-height: 1.5;">
            Tài liệu này đã được ghi nhận trong danh mục thư viện nhưng hiện chưa có bản sao vật lý khả dụng để mượn. Bạn đọc quan tâm có thể liên hệ quầy thủ thư để đề xuất bổ sung bản in.
          </p>
        </div>
      <?php endif; ?>

      <!-- Specs Grid: PAPER-HAVEN-BOOK-SPECS-20261018 -->
      <?php
        $pagesText = trim((string) ($meta['pages'] ?? ''));
        if ($pagesText !== '' && ctype_digit($pagesText)) $pagesText .= ' trang';
        if ($pagesText === '') $pagesText = 'Chưa cập nhật';

        $formatText = trim((string) ($meta['format'] ?? ''));
        $bookDimensions = 'Chưa cập nhật';
        if (preg_match('/(?<!\d)(\d+(?:[.,]\d+)?)\s*[xX×]\s*(\d+(?:[.,]\d+)?)\s*cm/ui', $formatText, $dimMatch)) {
            $bookDimensions = str_replace(',', '.', $dimMatch[1]) . ' × ' . str_replace(',', '.', $dimMatch[2]) . ' cm';
        }
        $bindingText = preg_replace('/(?<!\d)\d+(?:[.,]\d+)?\s*[xX×]\s*\d+(?:[.,]\d+)?\s*cm/ui', '', $formatText);
        $bindingText = trim((string) preg_replace('/[\s·,;–—-]+$/u', '', (string) $bindingText));
        if ($bindingText === '' || $bindingText === 'Bìa tiêu chuẩn') $bindingText = 'Chưa xác minh';
        $isbnValue = trim((string) ($book['isbn'] ?? ''));
      ?>
      <div class="book-v9-specs-grid paperhaven-specs-v14">
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Nhà xuất bản</div>
          <div class="book-v9-spec-value" title="<?= e($book['publisher'] ?: 'Chưa cập nhật') ?>"><?= e($book['publisher'] ?: 'Chưa cập nhật') ?></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Năm phát hành</div>
          <div class="book-v9-spec-value"><?= e($book['publication_year'] ?: 'Chưa cập nhật') ?></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Mã ISBN</div>
          <?php if ($isbnValue !== ''): ?>
            <div class="book-v9-spec-value book-v9-isbn" title="<?= e($isbnValue) ?>"><code class="isbn-code"><?= e($isbnValue) ?></code></div>
          <?php else: ?>
            <div class="book-v9-spec-value"><span class="isbn-status-badge isbn-status-unverified" title="Chưa có mã ISBN được đối chiếu xác minh theo đúng ấn bản này">Chưa có ISBN (Chờ đối soát)</span></div>
          <?php endif; ?>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Số trang</div>
          <div class="book-v9-spec-value"><span class="spec-nowrap"><?= e($pagesText) ?></span></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Khổ sách</div>
          <div class="book-v9-spec-value"><span class="spec-nowrap"><?= e($bookDimensions) ?></span></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Loại bìa / Quy cách</div>
          <div class="book-v9-spec-value"><span class="spec-binding"><?= e($bindingText) ?></span></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Ngôn ngữ ấn bản</div>
          <div class="book-v9-spec-value"><?= e($meta['language'] ?? 'Chưa cập nhật') ?></div>
        </div>
        <div class="book-v9-spec-card">
          <div class="book-v9-spec-label">Mã quản lý thư viện</div>
          <div class="book-v9-spec-value"><strong><?= e($book['book_code']) ?></strong></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Section 1: Detailed Overview & Synopsis -->
  <section class="book-v9-card" id="synopsis">
    <div class="book-v9-card-header">
      <div>
        <span class="eyebrow">GIỚI THIỆU TÁC PHẨM</span>
        <h2>Giới thiệu sách</h2>
      </div>
    </div>

    <!-- Demo / Internal Record Notice if applicable -->
    <?php if (($meta['verification_status'] ?? '') === 'DEMO_DATA_SUSPECTED'): ?>
      <div class="demo-notice-banner" role="note">
        <div class="icon">ℹ</div>
        <div>
          <div class="demo-notice-title">Thông báo minh bạch dữ liệu thư viện: Bản ghi mẫu thử nghiệm</div>
          <p class="demo-notice-desc">
            Hồ sơ tài liệu này được tạo lập trong giai đoạn thiết lập danh mục mẫu của hệ thống Paper Haven nhằm phục vụ bài giảng nhập môn kỹ thuật lập trình và kiểm thử chức năng mượn trả.
            Thông tin tác giả <em>"<?= e($book['author']) ?>"</em> và nhà xuất bản <em>"<?= e($book['publisher']) ?>"</em> là quy ước thử nghiệm.
            Bạn đọc có thể tham khảo trực tiếp các bản sao giáo trình và bài tập thực hành được lưu trữ tại phòng máy thư viện.
          </p>
        </div>
      </div>
    <?php endif; ?>

    <!-- Rich Synopsis paragraphs -->
    <div class="book-v9-synopsis">
      <?php
      $richDesc = trim((string) ($meta['rich_description'] ?? ''));
      $bookDesc = trim((string) ($book['description'] ?? ''));
      $descToUse = $richDesc !== '' ? $richDesc : ($bookDesc !== '' ? $bookDesc : 'Thông tin giới thiệu đang được cập nhật.');
      $paragraphs = preg_split('/\n\s*\n/', $descToUse);
      foreach ($paragraphs as $para):
        $para = trim($para);
        if ($para !== ''):
      ?>
        <p><?= nl2br(e($para)) ?></p>
      <?php
        endif;
      endforeach;
      ?>
    </div>

    <!-- Table of Contents if available -->
    <?php if (!empty($meta['table_of_contents']) && is_array($meta['table_of_contents'])): ?>
      <div style="margin-top: 2rem; border-top: 1px dashed var(--beige); padding-top: 1.5rem;">
        <h3 style="color: var(--forest); font-size: 1.15rem; margin-bottom: 0.75rem;">Mục lục & Các chủ đề trọng tâm</h3>
        <ul class="book-v9-toc-grid">
          <?php foreach ($meta['table_of_contents'] as $idx => $chapter): ?>
            <li class="book-v9-toc-item">
              <span class="book-v9-toc-num"><?= sprintf('%02d', $idx + 1) ?></span>
              <span><?= e($chapter) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Source attribution / Copyright note -->
    <?php
      $sourceUrl = trim((string) ($meta['source_url'] ?? ''));
      if (!filter_var($sourceUrl, FILTER_VALIDATE_URL) || !in_array(strtolower((string) parse_url($sourceUrl, PHP_URL_SCHEME)), ['http', 'https'], true)) $sourceUrl = '';
      $license = trim((string) ($meta['license'] ?? ''));
      $bCode = (string) ($book['book_code'] ?? '');
      $isStandardEbooks = preg_match('~^https://(?:www\.)?standardebooks\.org/~i', $sourceUrl) === 1 && str_starts_with($bCode, 'SE-');
      $isOpenAccess = !$isStandardEbooks && $sourceUrl !== '' && !empty($meta['license_url']) && preg_match('/^(?:CC[ -]|GNU|Open Access)/i', $license) === 1;
    ?>
    <?php if ($isStandardEbooks && $sourceUrl !== ''): ?>
      <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: var(--paper); border-radius: 6px; font-size: 0.82rem; color: var(--muted); border-left: 3px solid var(--forest);">
        <strong style="color: var(--forest);">Phạm vi công cộng & Số hóa:</strong>
        Tác phẩm văn học kinh điển thuộc phạm vi công cộng quốc tế (Public Domain), bản số hóa toàn văn được hiệu đính theo chuẩn Standard Ebooks.
        <a class="text-link" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 0.25rem;">Xem bản ghi Standard Ebooks ↗</a>
        <?php if (!empty($meta['license_url'])): ?>
          · Giấy phép: <a class="text-link" href="<?= e($meta['license_url']) ?>" target="_blank" rel="noopener noreferrer">CC0-1.0</a>
        <?php endif; ?>
      </div>
    <?php elseif ($isOpenAccess && $sourceUrl !== ''): ?>
      <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: var(--paper); border-radius: 6px; font-size: 0.82rem; color: var(--muted); border-left: 3px solid var(--forest);">
        <strong style="color: var(--forest);">Tài liệu học thuật Open Access:</strong>
        Tài liệu học thuật xuất bản theo giấy phép mở (<?= e($license) ?>).
        <a class="text-link" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 0.25rem;">Xem nguồn tài liệu ↗</a>
      </div>
    <?php elseif ($sourceUrl !== ''): ?>
      <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: var(--paper); border-radius: 6px; font-size: 0.82rem; color: var(--muted); border-left: 3px solid var(--gold);">
        <strong style="color: var(--forest);">Nguồn thông tin xuất bản:</strong>
        Đối chiếu dữ liệu thư mục từ nguồn tham khảo được lưu trong hồ sơ; không mặc định đây là ấn bản mở hoặc có quyền đọc toàn văn.
        <a class="text-link" href="<?= e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 0.25rem;">Xem nguồn đối chiếu ↗</a>
      </div>
    <?php elseif ($coverSource = isbn_cover_source($book)): ?>
      <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; background: var(--paper); border-radius: 6px; font-size: 0.82rem; color: var(--muted); border-left: 3px solid var(--border);">
        <strong style="color: var(--forest);">Đối chiếu thư mục:</strong>
        Ấn bản tham chiếu theo ISBN <?= e($coverSource['isbn']) ?> (<em><?= e($coverSource['source_title']) ?></em>).
        <a class="text-link" href="<?= e($coverSource['source_url']) ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 0.25rem;">Đối chiếu bản ghi Open Library ↗</a>
      </div>
    <?php endif; ?>
  </section>

  <!-- Section 2: Detailed Bibliographic Record -->
  <section class="book-v9-card" id="biblio">
    <div class="book-v9-card-header">
      <div>
        <span class="eyebrow">HỒ SƠ THƯ MỤC</span>
        <h2>Thông số biên mục & Định danh thư viện chuẩn</h2>
      </div>
      <small style="color: var(--muted);">Thông tin biên mục và nguồn tham khảo</small>
    </div>

    <table class="book-v9-biblio-table">
      <tbody>
        <tr>
          <th>Mã tài liệu (Call Number)</th>
          <td><strong><?= e($book['book_code']) ?></strong></td>
        </tr>
        <tr>
          <th>Nhan đề chính</th>
          <td><strong><?= e($book['title']) ?></strong></td>
        </tr>
        <?php if (!empty($meta['original_title']) && $meta['original_title'] !== $book['title']): ?>
          <tr>
            <th>Nhan đề nguyên tác</th>
            <td><em><?= e($meta['original_title']) ?></em></td>
          </tr>
        <?php endif; ?>
        <tr>
          <th>Tác giả / Tác quyền</th>
          <td><?= e($book['author']) ?><?= !empty($meta['author_original']) && $meta['author_original'] !== $book['author'] ? ' (' . e($meta['author_original']) . ')' : '' ?></td>
        </tr>
        <tr>
          <th>Dịch giả / Hiệu đính</th>
          <td><?= $hasTranslator ? e($meta['translator']) : '<em>Không áp dụng (Ấn bản nguyên tác)</em>' ?></td>
        </tr>
        <tr>
          <th>Chủ đề & Phân loại DDC</th>
          <td><?= e($book['category_name'] ?? 'Khoa học') ?><?= !empty($meta['ddc']) ? ' — Chỉ số DDC: ' . e($meta['ddc']) : '' ?></td>
        </tr>
        <tr>
          <th>Nhà xuất bản</th>
          <td><?= e($book['publisher'] ?: 'Đang cập nhật') ?></td>
        </tr>
        <tr>
          <th>Năm xuất bản</th>
          <td><?= e($book['publication_year'] ?: '—') ?></td>
        </tr>
        <tr>
          <th>Mã tiêu chuẩn ISBN</th>
          <td><?= e($book['isbn'] ?: 'Chưa có thông tin ISBN xác minh') ?><?= !empty($meta['isbn13']) && $meta['isbn13'] !== $book['isbn'] ? ' (ISBN-13: ' . e($meta['isbn13']) . ')' : '' ?></td>
        </tr>
        <tr>
          <th>Số trang & Khổ in</th>
          <td><?= e($meta['pages'] ?? 'Đang cập nhật') ?> <?= !empty($meta['format']) ? '· ' . e($meta['format']) : '' ?></td>
        </tr>
        <tr>
          <th>Ngôn ngữ tài liệu</th>
          <td><?= e($meta['language'] ?? 'Tiếng Việt') ?></td>
        </tr>
        <tr>
          <th>Đối tượng phục vụ</th>
          <td><?= e($meta['target_audience'] ?? 'Toàn thể bạn đọc và thành viên thư viện Paper Haven') ?></td>
        </tr>
        <tr>
          <th>Bằng chứng xác minh</th>
          <td>
            <span class="status-badge <?= ($meta['verification_status'] ?? '') === 'VERIFIED' ? 'badge-success' : (($meta['verification_status'] ?? '') === 'PARTIALLY_VERIFIED' ? 'badge-info' : 'badge-warning') ?>">
              <?= e($meta['verification_badge'] ?? 'Đang đối chiếu') ?>
            </span>
            <div style="font-size: 0.8rem; color: var(--muted); margin-top: 0.35rem;"><?= e($meta['provenance_note'] ?? '') ?></div>
          </td>
        </tr>
      </tbody>
    </table>
  </section>

  <!-- Section 3: Physical Shelf Copies & Barcodes -->
  <?php if (!empty($copies)): ?>
    <section class="book-v9-card" id="copies">
      <div class="book-v9-card-header">
        <div>
          <span class="eyebrow">TỒN KHO & BẢN SAO VẬT LÝ</span>
          <h2>Vị trí kệ sách & Trạng thái lưu hành (<?= count($copies) ?> bản)</h2>
        </div>
      </div>

      <div class="table-scroll">
        <table class="data-table" style="width: 100%;">
          <thead>
            <tr>
              <th>Mã bản sao</th>
              <th>Vị trí kệ sách</th>
              <th>Tình trạng vật lý</th>
              <th>Trạng thái lưu hành</th>
              <th>Mã vạch / QR</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($copies as $copy): ?>
              <tr>
                <td><strong><?= e($copy['copy_code']) ?></strong></td>
                <td>
                  <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; background: var(--paper); border-radius: 4px; font-weight: 600; color: var(--forest); border: 1px solid var(--beige);">
                    ⌖ <?= e($copy['shelf_location'] ?: 'Chưa gán kệ') ?>
                  </span>
                </td>
                <td>
                  <span style="color: <?= $copy['item_condition'] === 'good' ? '#2e7d32' : ($copy['item_condition'] === 'worn' ? '#c58b54' : '#a13b30') ?>; font-weight: 500;">
                    <?= e(['good'=>'Tốt (Nguyên vẹn)','worn'=>'Hao mòn nhẹ','damaged'=>'Hư hỏng','lost'=>'Mất'][$copy['item_condition']] ?? $copy['item_condition']) ?>
                  </span>
                </td>
                <td>
                  <span class="status-badge <?= $copy['status'] === 'available' ? 'badge-success' : ($copy['status'] === 'on_loan' ? 'badge-warning' : ($copy['status'] === 'pending_audit' ? 'badge-info' : 'badge-muted')) ?>">
                    <?= e(['available'=>'Có sẵn tại quầy','on_loan'=>'Đang mượn','reserved'=>'Đã giữ chỗ','damaged'=>'Hư hỏng','lost'=>'Đã mất','retired'=>'Ngừng lưu hành','pending_audit'=>'Chờ kiểm kê lưu kho'][$copy['status']] ?? $copy['status']) ?>
                  </span>
                </td>
                <td>
                  <div style="display: inline-flex; gap: 6px; align-items: center;">
                    <button type="button" class="btn-code-action barcode-btn" data-code-preview data-code-type="barcode" data-code-endpoint="<?= e(url('/barcode/' . rawurlencode($copy['copy_code']))) ?>" data-copy-code="<?= e($copy['copy_code']) ?>" data-shelf-location="<?= e($copy['shelf_location']) ?>" title="Xem và in mã vạch Code 128">
                      <span class="code-icon">|||</span> Mã vạch
                    </button>
                    <button type="button" class="btn-code-action qr-btn" data-code-preview data-code-type="qr" data-code-endpoint="<?= e(url('/qr/' . rawurlencode($copy['copy_code']))) ?>" data-copy-code="<?= e($copy['copy_code']) ?>" data-shelf-location="<?= e($copy['shelf_location']) ?>" title="Xem mã QR định danh bản sao">
                      <span class="code-icon">▦</span> QR Code
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>

  <!-- Section 4: Borrowing Policies & Reader Guidelines -->
  <section class="book-v9-card" id="policy">
    <div class="book-v9-card-header">
      <div>
        <span class="eyebrow">QUY CHẾ THƯ VIỆN</span>
        <h2>Chính sách mượn trả & Quyền lợi bạn đọc</h2>
      </div>
    </div>

    <div class="book-v9-policy-grid">
      <div class="book-v9-policy-card">
        <h4>1. Thời hạn mượn tiêu chuẩn</h4>
        <p>Mỗi bạn đọc được mượn tối đa <strong>14 ngày</strong> cho mỗi bản sách in. Hệ thống sẽ tự động nhắc hạn trước 02 ngày qua email hoặc thông báo tài khoản.</p>
      </div>
      <div class="book-v9-policy-card">
        <h4>2. Gia hạn thời gian mượn</h4>
        <p>Được gia hạn tối đa <strong>02 lần</strong> (mỗi lần thêm 07 ngày) trực tiếp tại trang Tài khoản nếu cuốn sách này không có bạn đọc khác đang đặt trước.</p>
      </div>
      <div class="book-v9-policy-card">
        <h4>3. Biểu phí quá hạn & bồi hoàn</h4>
        <p>Phí quá hạn là <strong>5.000 đ / ngày / cuốn</strong> theo Nội quy Thư viện Paper Haven. Trường hợp làm mất hoặc hư hỏng nặng, bạn đọc bồi hoàn theo giá bìa tài liệu.</p>
      </div>
      <div class="book-v9-policy-card">
        <h4>4. Bảo quản tài liệu thư viện</h4>
        <p>Giữ gìn sách cẩn thận, không gấp nếp mép trang, không viết vẽ hoặc gạch chân bút mực lên sách. Báo ngay cho thủ thư khi phát hiện sách có dấu hiệu hư hại.</p>
      </div>
    </div>
  </section>

  <!-- Section 5: Reviews & Reader Ratings -->
  <section class="book-v9-card" id="reviews">
    <div class="book-v9-card-header">
      <div>
        <span class="eyebrow">Ý KIẾN BẠN ĐỌC</span>
        <h2>Đánh giá & Cảm nhận độc giả</h2>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <!-- Score summary card -->
      <div style="background: var(--paper); padding: 1.75rem; border-radius: 8px; text-align: center; border: 1px solid var(--beige);">
        <div style="font-size: 3.2rem; font-family: var(--serif); font-weight: 700; color: var(--forest); line-height: 1;">
          <?= number_format($avgRating, 1) ?>
        </div>
        <div style="color: var(--gold); font-size: 1.6rem; margin: 0.35rem 0;">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <?= $s <= round($avgRating) ? '★' : '☆' ?>
          <?php endfor; ?>
        </div>
        <p style="color: var(--muted); margin: 0; font-size: 0.88rem;">Dựa trên <strong><?= $totalReviews ?></strong> nhận xét từ cộng đồng đọc Paper Haven</p>
      </div>

      <!-- Submit review form -->
      <div>
        <?php if ($isReader): ?>
          <form action="<?= e(url('/book/' . (int) $book['id'] . '/review')) ?>" method="post" class="form-stack">
            <?= csrf_field() ?>
            <h3 style="margin-top: 0; color: var(--forest); font-size: 1.1rem;"><?= $userReview ? 'Cập nhật đánh giá của bạn' : 'Viết đánh giá của bạn cho cuốn sách này' ?></h3>
            <label>
              Số sao đánh giá
              <select name="rating" required style="max-width: 220px;">
                <option value="5" <?= ($userReview['rating'] ?? 5) == 5 ? 'selected' : '' ?>>★★★★★ (5 sao - Xuất sắc)</option>
                <option value="4" <?= ($userReview['rating'] ?? 5) == 4 ? 'selected' : '' ?>>★★★★☆ (4 sao - Rất hay)</option>
                <option value="3" <?= ($userReview['rating'] ?? 5) == 3 ? 'selected' : '' ?>>★★★☆☆ (3 sao - Khá)</option>
                <option value="2" <?= ($userReview['rating'] ?? 5) == 2 ? 'selected' : '' ?>>★★☆☆☆ (2 sao - Trung bình)</option>
                <option value="1" <?= ($userReview['rating'] ?? 5) == 1 ? 'selected' : '' ?>>★☆☆☆☆ (1 sao - Không thích)</option>
              </select>
            </label>
            <label>
              Nhận xét / Cảm nhận đọc sách
              <textarea name="comment" rows="3" maxlength="1000" placeholder="Chia sẻ suy nghĩ và cảm nhận của bạn về cuốn sách này để giúp các bạn đọc khác lựa chọn..."><?= e($userReview['comment'] ?? '') ?></textarea>
            </label>
            <button class="button button-primary" type="submit">Gửi nhận xét <span>→</span></button>
          </form>
        <?php else: ?>
          <div style="padding: 1.5rem; background: var(--paper); border-radius: 8px; border: 1px solid var(--beige);">
            <h3 style="margin-top: 0; color: var(--forest); font-size: 1.1rem;">Bạn đã thưởng thức cuốn sách này?</h3>
            <p style="color: var(--muted); margin-bottom: 1rem; font-size: 0.88rem;">Đăng nhập tài khoản độc giả để gửi nhận xét, chấm điểm sao và chia sẻ cảm xúc cùng cộng đồng đọc sách Paper Haven.</p>
            <a class="button button-primary" href="<?= e(url('/login')) ?>">Đăng nhập gửi đánh giá →</a>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Review list -->
    <?php if (!empty($reviews)): ?>
      <div style="display: flex; flex-direction: column; gap: 1rem;">
        <?php foreach ($reviews as $rev): ?>
          <div style="padding: 1.1rem 1.35rem; background: var(--paper); border-radius: 6px; border-left: 3px solid var(--forest); border: 1px solid var(--beige); border-left-width: 3px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem;">
              <div>
                <strong style="color: var(--forest);"><?= e($rev['full_name']) ?></strong>
                <span style="color: var(--gold); margin-left: 0.5rem; font-size: 1.1rem;">
                  <?= str_repeat('★', (int) $rev['rating']) . str_repeat('☆', 5 - (int) $rev['rating']) ?>
                </span>
              </div>
              <small style="color: var(--muted);"><?= e(date('d/m/Y H:i', strtotime($rev['created_at']))) ?></small>
            </div>
            <?php if (!empty($rev['comment'])): ?>
              <p style="margin: 0.65rem 0 0; color: var(--brown); font-style: italic; line-height: 1.6; font-size: 0.9rem;"><?= nl2br(e($rev['comment'])) ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color: var(--muted); font-style: italic; margin-top: 0.5rem;">Chưa có nhận xét nào. Hãy là người đầu tiên chia sẻ cảm nghĩ về cuốn sách này!</p>
    <?php endif; ?>
  </section>

  <!-- Section 6: Similar Books -->
  <?php if (!empty($similarBooks)): ?>
    <section class="book-v9-similar" id="similar-books">
      <div class="section-heading">
        <div>
          <span class="eyebrow">CÙNG CHỦ ĐỀ</span>
          <h2>Có thể bạn cũng <em>quan tâm</em></h2>
        </div>
        <a class="text-link" href="<?= e(url('/catalog?category=' . (int) ($book['category_id'] ?? 0))) ?>">Xem thêm sách <?= e($book['category_name'] ?? '') ?> →</a>
      </div>
      <div class="book-v9-similar-grid">
        <?php 
          $mainBook = $book;
          foreach ($similarBooks as $simBook): 
            $book = $simBook; 
            include dirname(__DIR__) . '/partials/book_card.php'; 
          endforeach; 
          $book = $mainBook;
        ?>
      </div>
    </section>
  <?php endif; ?>
</section>

<!-- Modal Preview Mã Vạch / QR Code -->
<div id="codePreviewModal" class="code-modal-overlay" data-book-title="<?= e($book['title']) ?>" style="display: none;">
  <div class="code-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalCodeTitle">
    <div class="code-modal-header">
      <div>
        <span class="eyebrow" id="modalCodeTypeLabel" style="font-size: 0.72rem; color: var(--gold); letter-spacing: 0.05em; text-transform: uppercase; font-weight: 700;">ĐỊNH DANH BẢN SAO</span>
        <h3 id="modalCodeTitle" style="margin: 2px 0 0; color: var(--forest); font-size: 1.15rem;">Mã định danh bản sao</h3>
      </div>
      <button type="button" class="code-modal-close" data-code-close aria-label="Đóng">&times;</button>
    </div>
    <div class="code-modal-body">
      <div class="code-modal-preview-box" id="modalSvgContainer">
        <div style="color: var(--muted); font-size: 0.85rem;">Đang tải mã...</div>
      </div>
      <div class="code-modal-info">
        <div style="font-family: monospace; font-size: 1.1rem; font-weight: 700; color: var(--forest); letter-spacing: 0.05em;" id="modalCopyCodeText">B005-0001</div>
        <div style="font-size: 0.82rem; color: var(--muted); margin-top: 4px;">
          Tựa sách: <strong style="color: var(--brown);"><?= e($book['title']) ?></strong>
        </div>
        <div style="font-size: 0.82rem; color: var(--forest); font-weight: 600; margin-top: 2px;" id="modalShelfText">
          ⌖ Vị trí kệ: Kệ N1-05
        </div>
      </div>
    </div>
    <div class="code-modal-footer">
      <button type="button" class="button button-quiet" data-code-close>Đóng</button>
      <a id="modalDirectLink" href="#" target="_blank" class="button button-outline" style="text-decoration: none;">Mở ảnh gốc ↗</a>
      <button type="button" class="button button-primary" data-code-print>In nhãn dán ⎙</button>
    </div>
  </div>
</div>

<script src="<?= e(asset('js/book-detail.js')) ?>" defer></script>
