<div class="page-title-row">
  <div><span class="eyebrow">VẬN HÀNH HỆ THỐNG</span><h1>Cài đặt</h1><p>Quản lý chính sách mượn, gia hạn và đặt trước của thư viện.</p></div>
</div>
<div class="dashboard-columns settings-columns">
  <section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">CHÍNH SÁCH THƯ VIỆN</span><h2>Quy định nghiệp vụ</h2><p class="muted">Phiên bản chính sách hiện tại: <?= (int) $policyVersion ?></p></div></div>
    <form class="form-stack" method="post" action="<?= e(url('/admin/settings')) ?>">
      <?= csrf_field() ?>
      <label>Tên thư viện<input type="text" name="library_name" minlength="2" maxlength="120" value="<?= e($libraryName) ?>" required></label>
      <div class="form-grid">
        <label>Số bản tối đa mỗi độc giả<input type="number" name="max_books_per_reader" min="1" max="50" value="<?= (int) $maxBooks ?>" required></label>
        <label>Thời hạn mượn mặc định (ngày)<input type="number" name="default_loan_days" min="1" max="90" value="<?= (int) $loanDays ?>" required></label>
        <label>Số lần gia hạn tối đa<input type="number" name="max_renewals" min="0" max="10" value="<?= (int) $maxRenewals ?>" required></label>
        <label>Số ngày mỗi lần gia hạn<input type="number" name="renewal_days" min="1" max="90" value="<?= (int) $renewalDays ?>" required></label>
        <label>Phí quá hạn mỗi ngày (đ)<input type="number" name="fine_per_overdue_day" min="0" max="10000000" step="0.01" value="<?= e((string) $dailyFine) ?>" required></label>
        <label>Thời hạn nhận sách đặt trước (ngày)<input type="number" name="reservation_expiry_days" min="1" max="30" value="<?= (int) $reservationExpiryDays ?>" required></label>
        <label>Phí sách hỏng (đ)<input type="number" name="damaged_copy_fine" min="0" max="100000000" step="0.01" value="<?= e((string) $damagedCopyFine) ?>" required></label>
        <label>Phí sách mất (đ)<input type="number" name="lost_copy_fine" min="0" max="100000000" step="0.01" value="<?= e((string) $lostCopyFine) ?>" required></label>
      </div>
      <div class="dialog-actions"><button class="button button-primary">Lưu chính sách</button></div>
    </form>
  </section>
  <section class="panel">
    <div class="panel-heading"><div><span class="eyebrow">MÔI TRƯỜNG MÁY CHỦ</span><h2>Thông tin vận hành</h2></div><span class="status-pill <?= $environment === 'production' ? 'returned' : 'borrowed' ?>"><?= e($environment) ?></span></div>
    <dl class="settings-list">
      <div><dt>Chế độ gỡ lỗi</dt><dd><?= $debug ? 'Đang bật' : 'Đã tắt' ?></dd></div>
      <div><dt>Múi giờ</dt><dd><?= e($timezone) ?></dd></div>
      <div><dt>Đăng ký độc giả công khai</dt><dd><?= $registration ? 'Đang mở' : 'Đang tắt' ?></dd></div>
      <div><dt>Cơ sở dữ liệu</dt><dd><?= e($databaseName) ?> <span class="status-pill returned">Đã kết nối</span></dd></div>
      <div><dt>PHP</dt><dd><?= e($phpVersion) ?></dd></div>
      <div><dt>PDO MySQL</dt><dd><?= in_array('mysql', $pdoDrivers, true) ? 'Đã cài đặt' : 'Chưa khả dụng' ?></dd></div>
    </dl>
    <div class="profile-help"><span>✳</span><p>Thông tin kết nối và cấu hình bảo mật tiếp tục được quản lý trong tệp <code>.env</code>, không đưa ra trình duyệt. Không bật APP_DEBUG trên máy chủ công khai.</p></div>
    <a class="button button-outline" href="<?= e(url('/')) ?>">Mở trang thư viện <span>↗</span></a>
  </section>
</div>
