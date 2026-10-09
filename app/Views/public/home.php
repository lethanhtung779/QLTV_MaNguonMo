<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow hero-eyebrow">MỘT KHÔNG GIAN CHO NHỮNG NGƯỜI YÊU SÁCH</span>
    <h1>Mỗi trang sách,<br><em>một thế giới mới.</em></h1>
    <p>Chậm lại một chút. Chọn một cuốn sách. Để những câu chuyện hay dẫn lối bạn đến những miền tri thức mới.</p>
    <div class="hero-actions"><a class="button button-cream" href="<?= e(url('/catalog')) ?>">Khám phá thư viện <span>→</span></a><a class="hero-sub-link" href="<?= e(url('/#about')) ?>">Câu chuyện của chúng tôi</a></div>
    <div class="hero-note"><span class="hero-note-icon">✳</span><span>Tri thức được sẻ chia<br><strong>cùng cộng đồng</strong></span></div>
  </div>
  <div class="hero-image" role="img" aria-label="Không gian đọc sách ấm cúng"></div>
  <div class="hero-side-note">ĐỌC CHẬM · SỐNG SÂU</div>
</section>
<section class="library-notice"><span class="notice-mark">✳</span><div><span class="eyebrow">THÔNG BÁO THƯ VIỆN</span><p><?= !empty($registrationOpen) ? 'Đăng ký thẻ độc giả trực tuyến đang mở. Tạo tài khoản để theo dõi lịch mượn, hạn trả và phí trễ hạn của bạn.' : 'Danh mục sách trực tuyến luôn mở để bạn khám phá và kiểm tra tình trạng sách trước khi ghé thư viện.' ?></p></div><?php if (!empty($registrationOpen)): ?><a class="text-link" href="<?= e(url('/register')) ?>">Đăng ký thẻ <span>→</span></a><?php else: ?><a class="text-link" href="<?= e(url('/catalog')) ?>">Xem danh mục <span>→</span></a><?php endif; ?></section>
<section class="content-section featured-section">
  <div class="section-heading"><div><span class="eyebrow">ĐƯỢC ĐỘC GIẢ YÊU MẾN</span><h2>Những cuốn sách <em>nổi bật</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Xem tất cả <span>→</span></a></div>
  <?php if ($popular): ?><div class="book-grid"><?php foreach ($popular as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><div class="empty-state"><span>✳</span><h3>Trang sách đầu tiên đang chờ bạn</h3><p>Thư viện sẽ sớm cập nhật danh mục sách.</p></div><?php endif; ?>
</section>
<section class="content-section new-books-section">
  <div class="section-heading"><div><span class="eyebrow">GÓC SÁCH MỚI</span><h2>Vừa lên kệ <em>thư viện</em></h2></div><a class="text-link" href="<?= e(url('/catalog')) ?>">Khám phá sách <span>→</span></a></div>
  <?php if ($newBooks): ?><div class="book-grid"><?php foreach ($newBooks as $book): include dirname(__DIR__) . '/partials/book_card.php'; endforeach; ?></div><?php else: ?><p class="muted">Các tựa sách mới sẽ được cập nhật tại đây.</p><?php endif; ?>
</section>
<section class="genre-band" id="genres"><div class="genre-band-heading"><span class="eyebrow">TÌM THEO TÂM TRẠNG</span><h2>Một thể loại cho <em>mỗi hành trình.</em></h2></div><div class="genre-links"><?php foreach ($categories as $category): ?><a href="<?= e(url('/catalog?category=' . (int) $category['id'])) ?>"><?= e($category['name']) ?><span>↗</span></a><?php endforeach; ?><?php if (!$categories): ?><a href="<?= e(url('/catalog')) ?>">Khám phá toàn bộ sách <span>↗</span></a><?php endif; ?></div></section>
<section class="about-section" id="about"><div class="about-image" role="img" aria-label="Kệ sách gỗ trong thư viện"></div><div class="about-copy"><span class="eyebrow">CÂU CHUYỆN PAPER & PAGE</span><h2>Không chỉ là nơi<br>lưu giữ <em>những cuốn sách.</em></h2><p>Chúng tôi tin rằng một cuốn sách đúng lúc có thể mở ra một góc nhìn mới. Paper & Page là không gian để tri thức được sẻ chia, những cuộc gặp gỡ bắt đầu và tình yêu đọc sách được nuôi dưỡng mỗi ngày.</p><a class="button button-outline" href="<?= e(url('/catalog')) ?>">Tìm cuốn sách tiếp theo <span>→</span></a></div></section>
<section class="loan-info"><span class="eyebrow">ĐỌC THẬT DỄ DÀNG</span><h2>Quy định mượn sách</h2><div class="loan-rules"><div><span>01</span><strong>Mượn tối đa 5 cuốn</strong><p>Mỗi thẻ độc giả được giữ tối đa 5 đầu sách cùng lúc.</p></div><div><span>02</span><strong>Thời hạn 14 ngày</strong><p>Bạn có thể gia hạn tại quầy nếu sách chưa được đặt trước.</p></div><div><span>03</span><strong>Trả sách đúng hẹn</strong><p>Phí trễ hạn được tính theo quy định thư viện hiện hành.</p></div></div></section>
