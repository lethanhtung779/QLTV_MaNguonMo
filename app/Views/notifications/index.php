<div class="page-title-row">
  <div><span class="eyebrow">TIN NHẮN TỪ THƯ VIỆN</span><h1>Thông báo</h1><p>Cập nhật về mượn trả, gia hạn và các yêu cầu đặt trước của bạn.</p></div>
  <?php if (array_filter($notifications, static fn(array $item): bool => $item['read_at'] === null)): ?>
    <form method="post" action="<?= e(url('/notifications/read')) ?>"><?= csrf_field() ?><input type="hidden" name="mark_all" value="1"><button class="button button-outline">Đánh dấu tất cả đã đọc</button></form>
  <?php endif; ?>
</div>
<section class="panel">
  <div class="panel-heading"><div><span class="eyebrow">HỘP THƯ</span><h2>Tin mới nhất</h2></div><span class="muted"><?= count($notifications) ?> thông báo</span></div>
  <?php if ($notifications): ?>
    <div class="activity-list notification-list">
      <?php foreach ($notifications as $notification): ?>
        <article class="activity-item notification-item <?= $notification['read_at'] === null ? 'unread' : '' ?>">
          <span class="activity-dot"></span>
          <div class="notification-copy"><strong><?= e($notification['title']) ?></strong><p><?= e($notification['message']) ?></p><small><?= e(date('d/m/Y H:i', strtotime($notification['created_at']))) ?> · <?= $notification['read_at'] === null ? 'Chưa đọc' : 'Đã đọc' ?></small></div>
          <?php if ($notification['read_at'] === null): ?><form method="post" action="<?= e(url('/notifications/read')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $notification['id'] ?>"><button class="icon-button">Đánh dấu đã đọc</button></form><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?><div class="empty-inline">Bạn chưa có thông báo nào.</div><?php endif; ?>
</section>
