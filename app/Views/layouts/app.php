<?php
$user = current_user();
$role = (string) ($user['role'] ?? '');
$reader = $role === 'reader';
$navigation = $reader
  ? [['/account', '⌂', 'Tổng quan'], ['/catalog', '▤', 'Khám phá sách'], ['/account/profile', '♙', 'Hồ sơ độc giả'], ['/notifications', '♧', 'Thông báo']]
  : [['/admin', '▦', 'Tổng quan'], ['/admin/books', '▤', 'Quản lý sách'], ['/admin/readers', '♙', 'Độc giả'], ['/admin/loans', '⇄', 'Mượn / Trả sách'], ['/admin/fines', '₫', 'Phí & thanh toán'], ['/admin/categories', '◇', 'Thể loại'], ['/admin/authors', '✎', 'Tác giả'], ['/admin/publishers', '▤', 'Nhà xuất bản'], ['/admin/statistics', '▥', 'Thống kê'], ['/profile', '♙', 'Hồ sơ tài khoản'], ['/notifications', '♧', 'Thông báo']];
if ($role === 'admin') {
  $navigation[] = ['/admin/settings', '⚙', 'Cài đặt'];
}
$route = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#193E30">
  <title><?= e($title ?? 'Quản lý') ?> · Paper & Page</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body class="staff-body">
  <div class="staff-shell">
    <aside class="staff-sidebar" data-sidebar>
      <a class="brand-lockup sidebar-brand" href="<?= e(url($reader ? '/account' : '/admin')) ?>">
        <span class="brand-mark">P<span>&</span></span>
        <span class="brand-name">PAPER <em>&</em> PAGE<small><?= $reader ? 'THẺ ĐỘC GIẢ' : 'THƯ VIỆN CỘNG ĐỒNG' ?></small></span>
      </a>
      <span class="sidebar-caption"><?= $reader ? 'KHÔNG GIAN CỦA BẠN' : 'QUẢN LÝ THƯ VIỆN' ?></span>
      <nav class="staff-nav">
        <?php foreach ($navigation as [$path, $icon, $label]): ?>
          <a class="staff-nav-link <?= '/' . $route === $path || ($path !== '/admin' && str_starts_with('/' . $route, $path . '/')) ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
            <span><?= e($icon) ?></span><?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-bottom">
        <?php if (!$reader): ?>
          <div class="sidebar-help"><span>✦</span><strong>Góc đọc yên bình</strong><small>Chăm chút từng trang sách, vun bồi cộng đồng đọc.</small></div>
        <?php endif; ?>
        <a class="staff-nav-link" href="<?= e(url('/')) ?>"><span>↗</span>Xem trang thư viện</a>
        <form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button class="staff-nav-link logout-button"><span>⇥</span>Đăng xuất</button></form>
      </div>
    </aside>
    <div class="staff-main">
      <header class="staff-header">
        <button class="mobile-menu-toggle staff-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-label="Mở menu">☰</button>
        <div class="breadcrumb"><a href="<?= e(url($reader ? '/account' : '/admin')) ?>">Paper & Page</a><span>/</span><strong><?= e($title ?? 'Tổng quan') ?></strong></div>
        <div class="staff-header-actions">
          <?php if (!$reader): ?>
            <form class="header-search staff-search" action="<?= e(url('/admin/books')) ?>" method="get"><label class="sr-only" for="staffSearch">Tìm kiếm</label><input id="staffSearch" type="search" name="q" placeholder="Tìm sách, độc giả..." value="<?= e(query_string('q')) ?>"><button aria-label="Tìm kiếm">⌕</button></form>
          <?php endif; ?>
          <a class="notification-link" href="<?= e(url('/notifications')) ?>" aria-label="Thông báo">
            ♧<?php if (($notificationUnreadCount ?? 0) > 0): ?><span class="notification-badge"><?= (int) $notificationUnreadCount > 99 ? '99+' : (int) $notificationUnreadCount ?></span><?php endif; ?>
          </a>
          <span class="header-divider"></span>
          <span class="account-avatar"><?= e(mb_strtoupper(mb_substr((string) ($user['name'] ?? 'P'), 0, 1))) ?></span>
          <span class="account-caption"><strong><?= e($user['name'] ?? 'Bạn đọc') ?></strong><small><?= $role === 'admin' ? 'Quản trị viên' : ($role === 'librarian' ? 'Thủ thư' : 'Độc giả') ?></small></span>
        </div>
      </header>
      <main class="staff-content">
        <?php if ($message = flash('success')): ?><div class="flash-message success" role="status"><?= e($message) ?></div><?php endif; ?>
        <?php if ($message = flash('error')): ?><div class="flash-message error" role="alert"><?= e($message) ?></div><?php endif; ?>
        <?= $content ?>
      </main>
      <footer class="staff-footer">Paper & Page <span>·</span> Quản lý thư viện cộng đồng</footer>
    </div>
  </div>
  <div class="mobile-backdrop" data-backdrop hidden></div>
</body>
</html>
