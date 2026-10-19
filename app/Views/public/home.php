<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow hero-eyebrow">PAPER HAVEN · THƯ VIỆN TRỰC TUYẾN</span>
    <h1>Khám phá cuốn sách<br><em>tiếp theo của bạn.</em></h1>
    <p>Tìm kiếm kho tri thức, kiểm tra sách còn sẵn và theo dõi hành trình đọc của bạn trong một không gian thư viện trực tuyến.</p>
    <form class="hero-search" action="<?= e(url('/catalog')) ?>" method="get">
      <label class="sr-only" for="heroSearch">Tìm theo tên sách, tác giả hoặc ISBN</label>
      <input id="heroSearch" type="search" name="q" placeholder="Tên sách, tác giả hoặc ISBN...">
      <button class="button button-cream" type="submit">Tìm sách <span>→</span></button>
    </form>
    <div class="hero-actions"><a class="button button-cream" href="<?= e(url('/catalog')) ?>">Khám phá thư viện <span>→</span></a><a class="hero-sub-link" href="<?= e(url('/#about')) ?>">Câu chuyện của chúng tôi</a></div>
    <div class="hero-note"><span class="hero-note-icon">✳</span><span>Tri thức được sẻ chia<br><strong>cùng cộng đồng</strong></span></div>
  </div>
  <div class="hero-image" role="img" aria-label="Không gian đọc sách ấm cúng"></div>
  <div class="hero-side-note">ĐỌC CHẬM · SỐNG SÂU</div>
</section>
<section class="library-notice"><span class="notice-mark">✳</span><div><span class="eyebrow">THÔNG BÁO THƯ VIỆN</span><p><?= !empty($registrationOpen) ? 'Đăng ký thẻ độc giả trực tuyến đang mở. Tạo tài khoản để theo dõi lịch mượn, hạn trả và phí trễ hạn của bạn.' : 'Danh mục sách trực tuyến luôn mở để bạn khám phá và kiểm tra tình trạng sách trước khi ghé thư viện.' ?></p></div><?php if (!empty($registrationOpen)): ?><a class="text-link" href="<?= e(url('/register')) ?>">Đăng ký thẻ <span>→</span></a><?php else: ?><a class="text-link" href="<?= e(url('/catalog')) ?>">Xem danh mục <span>→</span></a><?php endif; ?></section>
<section class="content-section featured-section">
  <div class="section-heading"><div><span class="eyebrow">TUYỂN CHỌN TỪ THƯ VIỆN</span><h2>Những cuốn sách <em>nổi bật</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Xem tất cả <span>→</span></a></div>
  <?php if ($featuredBooks): ?><div class="book-grid"><?php foreach ($featuredBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><div class="empty-state"><span>✳</span><h3>Trang sách đầu tiên đang chờ bạn</h3><p>Thư viện sẽ sớm cập nhật danh mục sách.</p></div><?php endif; ?>
</section>
<section class="content-section new-books-section">
  <div class="section-heading"><div><span class="eyebrow">GÓC SÁCH MỚI</span><h2>Vừa lên kệ <em>thư viện</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Khám phá sách <span>→</span></a></div>
  <?php if ($newBooks): ?><div class="book-grid"><?php foreach ($newBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Các tựa sách mới sẽ được cập nhật tại đây.</p><?php endif; ?>
</section>
<section class="content-section popular-books-section">
  <div class="section-heading"><div><span class="eyebrow">ĐƯỢC BẠN ĐỌC QUAN TÂM</span><h2>Sách được mượn <em>nhiều</em></h2></div><a class="text-link" href="<?= e(url('/catalog?sort=popular')) ?>">Xem danh mục <span>→</span></a></div>
  <?php if ($popularBooks): ?><div class="book-grid"><?php foreach ($popularBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Dữ liệu lượt mượn sẽ được cập nhật tại đây.</p><?php endif; ?>
</section>
<section class="content-section recommendation-section">
  <div class="section-heading"><div><span class="eyebrow">GỢI Ý ĐANG CÓ SẴN</span><h2>Sách dành <em>cho bạn</em></h2></div><a class="text-link" href="<?= e(url('/catalog?availability=available')) ?>">Xem sách còn sẵn <span>→</span></a></div>
  <p class="section-lead">Gợi ý hiện được chọn từ các đầu sách công khai đang còn bản khả dụng.</p>
  <?php if ($recommendedBooks): ?><div class="book-grid"><?php foreach ($recommendedBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Chưa có đầu sách khả dụng để gợi ý.</p><?php endif; ?>
</section>
<section class="content-section" id="all-books">
  <div class="section-heading"><div><span class="eyebrow">DANH MỤC THƯ VIỆN</span><h2>Khám phá <em>tất cả sách</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Tìm kiếm và lọc sách <span>→</span></a></div>
  <p class="results-count">Có <strong><?= (int) $pagination->total ?></strong> đầu sách trong danh mục công khai</p>
  <?php if ($homeBooks): ?><div class="book-grid catalog-grid"><?php foreach ($homeBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Chưa có sách trong danh mục công khai.</p><?php endif; ?>
  <?php
    $paginationAction = url('/');
    $paginationAnchor = 'all-books';
    include dirname(__DIR__) . '/partials/catalog_pagination.php';
    unset($paginationAction, $paginationAnchor);
  ?>
</section>
<section class="genre-band" id="genres"><div class="genre-band-heading"><span class="eyebrow">TÌM THEO TÂM TRẠNG</span><h2>Một thể loại cho <em>mỗi hành trình.</em></h2></div><div class="genre-links"><?php foreach ($categories as $category): ?><a href="<?= e(url('/catalog?category=' . (int) $category['id'])) ?>"><?= e($category['name']) ?><span>↗</span></a><?php endforeach; ?><?php if (!$categories): ?><a href="<?= e(url('/catalog')) ?>">Khám phá toàn bộ sách <span>↗</span></a><?php endif; ?></div></section>
<section class="collection-section">
  <div class="section-heading"><div><span class="eyebrow">BỘ SƯU TẬP THEO CHỦ ĐỀ</span><h2>Chọn một miền <em>tri thức</em></h2></div></div>
  <div class="collection-grid"><?php foreach ($categoryCollections as $collection): ?><a href="<?= e(url('/catalog?category=' . (int) $collection['id'])) ?>"><strong><?= e($collection['name']) ?></strong><span><?= (int) $collection['book_count'] ?> đầu sách</span><em>Khám phá →</em></a><?php endforeach; ?></div>
</section>
<section class="about-section" id="about"><div class="about-image" role="img" aria-label="Kệ sách gỗ trong thư viện"></div><div class="about-copy"><span class="eyebrow">CÂU CHUYỆN PAPER HAVEN</span><h2>Không chỉ là nơi<br>lưu giữ <em>những cuốn sách.</em></h2><p>Paper Haven kết nối bạn đọc với kho sách, giúp việc tìm kiếm, đặt trước và theo dõi lịch mượn trở nên rõ ràng, thuận tiện hơn mỗi ngày.</p><a class="button button-outline" href="<?= e(url('/catalog')) ?>">Tìm cuốn sách tiếp theo <span>→</span></a></div></section>
<section class="loan-info"><span class="eyebrow">ĐỌC THẬT DỄ DÀNG</span><h2>Quy trình mượn sách</h2><div class="loan-rules"><div><span>01</span><strong>Tìm và chọn sách</strong><p>Tra cứu tên sách, tác giả hoặc ISBN và kiểm tra số bản đang có sẵn.</p></div><div><span>02</span><strong>Mượn tối đa <?= (int) $policy['max_books_per_reader'] ?> cuốn</strong><p>Thời hạn mặc định <?= (int) $policy['default_loan_days'] ?> ngày, theo chính sách đang có hiệu lực.</p></div><div><span>03</span><strong>Theo dõi và trả đúng hẹn</strong><p>Độc giả có thể gia hạn tối đa <?= (int) $policy['max_renewals'] ?> lần khi đủ điều kiện.</p></div></div></section>
