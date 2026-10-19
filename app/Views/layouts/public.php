<?php $user = current_user(); ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#193E30">
  <meta name="description" content="Paper Haven — Hệ thống quản lý thư viện trực tuyến, nơi bạn khám phá và quản lý hành trình đọc sách.">
  <title><?= e($title ?? 'Paper Haven') ?> · Paper Haven</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/catalog-layout-fix.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="public-body">
  <header class="public-header">
    <a class="brand-lockup" href="<?= e(url('/')) ?>" aria-label="Paper Haven — trang chủ">
      <span class="brand-mark">P<span>H</span></span>
      <span class="brand-name">PAPER <em>HAVEN</em><small>THƯ VIỆN TRỰC TUYẾN</small></span>
    </a>
    <button class="mobile-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-label="Mở menu">☰</button>
    <nav class="public-nav" data-mobile-menu>
      <a href="<?= e(url('/')) ?>">Trang chủ</a>
      <a href="<?= e(url('/catalog')) ?>">Sách</a>
      <a href="<?= e(url('/')) ?>#genres">Thể loại</a>
      <a href="<?= e(url('/')) ?>#about">Giới thiệu</a>
      <a href="<?= e(url('/')) ?>#contact">Liên hệ</a>
    </nav>
    <div class="header-actions">
      <form class="header-search" action="<?= e(url('/catalog')) ?>" method="get">
        <label class="sr-only" for="headerSearch">Tìm kiếm sách</label>
        <input id="headerSearch" type="search" name="q" placeholder="Tìm một cuốn sách..." value="<?= e(query_string('q')) ?>">
        <button aria-label="Tìm kiếm">⌕</button>
      </form>
      <?php if ($user): ?>
        <a class="button button-quiet button-small" href="<?= e(url($user['role'] === 'reader' ? '/account' : '/admin')) ?>"><?= e($user['name']) ?></a>
      <?php else: ?>
        <a class="button button-quiet button-small login-link" href="<?= e(url('/login')) ?>">Đăng nhập</a>
        <?php if (\Library\Core\Config::bool('ALLOW_PUBLIC_REGISTRATION', true)): ?>
          <a class="button button-primary button-small register-link" href="<?= e(url('/register')) ?>">Đăng ký thẻ</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </header>

  <?php if ($message = flash('success')): ?><div class="flash-message success" role="status"><?= e($message) ?></div><?php endif; ?>
  <?php if ($message = flash('error')): ?><div class="flash-message error" role="alert"><?= e($message) ?></div><?php endif; ?>
  <main><?= $content ?></main>
  <footer class="public-footer" id="contact">
    <div class="footer-main">
      <a class="brand-lockup footer-brand" href="<?= e(url('/')) ?>">
        <span class="brand-mark">P<span>H</span></span>
        <span class="brand-name">PAPER <em>HAVEN</em><small>MỖI CUỐN SÁCH, MỘT HÀNH TRÌNH</small></span>
      </a>
      <p>Không gian đọc dành cho những tâm hồn yêu tri thức và những câu chuyện được kể qua trang sách.</p>
      <div class="footer-contact"><span>⌖ Hà Nội, Việt Nam</span><span>Thư viện trực tuyến</span></div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Paper Haven · Hệ thống quản lý thư viện trực tuyến</span><a href="<?= e(url('/catalog')) ?>">Khám phá sách →</a></div>
  </footer>
<?php require dirname(__DIR__) . '/partials/chat_widget.php'; ?>
</body>
</html>
