<?php
$user = current_user();
$role = (string) ($user['role'] ?? '');
$reader = $role === 'reader';

if ($reader) {
    $navigationGroups = [
        'KHÔNG GIAN ĐỘC GIẢ' => [
            ['/account', '⌂', 'Tổng quan'],
            ['/account/bookshelf', '♥', 'Tủ sách của tôi'],
            ['/catalog', '▤', 'Khám phá sách'],
            ['/account/profile', '♙', 'Hồ sơ độc giả'],
            ['/notifications', '♧', 'Thông báo'],
        ]
    ];
} else {
    $navigationGroups = [
        'TỔNG QUAN' => [
            ['/admin', '▦', 'Bảng điều khiển'],
            ['/admin/statistics', '▥', 'Báo cáo thống kê'],
            ['/admin/metadata-quality', '✓', 'Chất lượng Metadata'],
        ],
        'KHO & BIÊN MỤC' => [
            ['/admin/books', '▤', 'Quản lý sách'],
            ['/admin/books/import', '＋', 'Nhập sách hàng loạt'],
            ['/admin/inventory/audits', '✓', 'Kiểm kê kho'],
            ['/admin/print-labels', '▤', 'In tem & Mã vạch'],
            ['/admin/categories', '◇', 'Thể loại'],
            ['/admin/authors', '✎', 'Tác giả'],
            ['/admin/publishers', '▤', 'Nhà xuất bản'],
        ],
        'LƯU HÀNH & ĐỘC GIẢ' => [
            ['/admin/loans', '⇄', 'Mượn / Trả sách'],
            ['/admin/readers', '♙', 'Độc giả'],
            ['/admin/fines', '₫', 'Phí & thanh toán'],
            ['/admin/reviews', '★', 'Đánh giá bạn đọc'],
        ],
    ];

    if ($role === 'admin') {
        $navigationGroups['QUẢN TRỊ HỆ THỐNG'] = [
            ['/admin/users', '♙', 'Nhân viên'],
            ['/admin/permissions', '◇', 'Phân quyền'],
            ['/admin/activity', '▤', 'Nhật ký hoạt động'],
            ['/admin/settings', '⚙', 'Cài đặt hệ thống'],
        ];
    }
}

// Filter permissions for staff
$filteredGroups = [];
foreach ($navigationGroups as $groupTitle => $links) {
    $validLinks = [];
    foreach ($links as $link) {
        if ($reader) {
            $validLinks[] = $link;
        } else {
            $permission = \Library\Core\Permissions::route('GET', $link[0]);
            if ($permission === null || can($permission)) {
                $validLinks[] = $link;
            }
        }
    }
    if (!empty($validLinks)) {
        $filteredGroups[$groupTitle] = $validLinks;
    }
}

$route = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#193E30">
  <title><?= e($title ?? 'Quản lý') ?> · Paper Haven</title>
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
        <span class="brand-mark">P<span>H</span></span>
        <span class="brand-name">PAPER <em>HAVEN</em><small><?= $reader ? 'THẺ ĐỘC GIẢ' : 'THƯ VIỆN TRỰC TUYẾN' ?></small></span>
      </a>
      <nav class="staff-nav">
        <?php foreach ($filteredGroups as $groupTitle => $links): ?>
          <div class="sidebar-nav-group">
            <span class="sidebar-group-heading"><?= e($groupTitle) ?></span>
            <?php foreach ($links as [$path, $icon, $label]): ?>
              <?php 
                $isActive = '/' . $route === $path || ($path !== '/admin' && str_starts_with('/' . $route, $path . '/'));
              ?>
              <a class="staff-nav-link <?= $isActive ? 'active' : '' ?>" href="<?= e(url($path)) ?>" title="<?= e($label) ?>">
                <span class="nav-icon"><?= e($icon) ?></span>
                <span class="nav-label"><?= e($label) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </nav>
      <div class="sidebar-bottom">
        <a class="staff-nav-link" href="<?= e(url('/')) ?>"><span>↗</span>Xem trang thư viện</a>
        <form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button class="staff-nav-link logout-button"><span>⇥</span>Đăng xuất</button></form>
      </div>
    </aside>
    <div class="staff-main">
      <header class="staff-header">
        <button class="mobile-menu-toggle staff-menu-toggle" type="button" data-menu-toggle aria-expanded="false" aria-label="Mở menu">☰</button>
        <div class="breadcrumb"><a href="<?= e(url($reader ? '/account' : '/admin')) ?>">Paper Haven</a><span>/</span><strong><?= e($title ?? 'Tổng quan') ?></strong></div>
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
      <footer class="staff-footer">Paper Haven <span>·</span> Hệ thống quản lý thư viện trực tuyến</footer>
    </div>
  </div>
  <div class="mobile-backdrop" data-backdrop hidden></div>
<?php require dirname(__DIR__) . '/partials/chat_widget.php'; ?>
</body>
</html>
