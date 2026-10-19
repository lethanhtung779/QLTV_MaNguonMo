<?php $statuses=['available'=>'Có sẵn','damaged'=>'Hỏng','lost'=>'Mất','retired'=>'Ngừng sử dụng','on_loan'=>'Đang mượn','reserved'=>'Giữ chỗ']; $conditions=['good'=>'Tốt','worn'=>'Cũ','damaged'=>'Hỏng','lost'=>'Mất']; ?>
<div class="page-title-row"><div><span class="eyebrow">KHO SÁCH</span><h1>Bản sao sách</h1><p><?= e($book['title']) ?> · <?= e($book['book_code']) ?> · <?= (int)$book['available_copies'] ?> bản có sẵn</p></div><div style="display: flex; gap: 0.5rem;"><a class="button button-primary" href="<?= e(url('/admin/books/' . (int)$book['id'] . '/labels')) ?>" target="_blank">In tem mã QR/Barcode 🖨</a><a class="button button-outline" href="<?= e(url('/admin/books')) ?>">Danh mục sách</a></div></div>
<section class="panel"><div class="table-book"><?= book_thumbnail($book) ?><div><strong><?= e($book['title']) ?></strong><small><?= e($book['author']) ?></small></div></div></section>
<section class="panel">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <h2>Thêm bản sao mới (Nhập kho)</h2>
    <button type="button" class="button button-small button-outline" id="btn-autofill-copy" style="font-size: 0.85rem;">
      ✨ Tự động điền mã tiếp theo
    </button>
  </div>
  <form class="form-stack" method="post" action="<?= e(url('/admin/books/'.$book['id'].'/copies')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="0">
    <div class="form-grid">
      <label>Mã bản sao
        <input id="input-copy-code" name="copy_code" maxlength="60" required placeholder="CPY-<?= e($book['book_code']) ?>-01">
      </label>
      <label>Mã vạch (Barcode)
        <input id="input-barcode" name="barcode" maxlength="80" placeholder="Trùng mã bản sao hoặc mã in riêng">
      </label>
      <label>Vị trí kệ
        <input id="input-shelf" name="shelf_location" maxlength="120" placeholder="Ví dụ: Kệ A1-03">
      </label>
      <label>Tình trạng
        <select name="item_condition">
          <?php foreach($conditions as $key=>$label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Trạng thái
        <select name="status">
          <?php foreach(array_slice($statuses,0,4) as $key=>$label): ?>
            <option value="<?= e($key) ?>"><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>
    <button class="button button-primary">Thêm bản sao vào kho</button>
  </form>
</section>

<script>
document.getElementById('btn-autofill-copy')?.addEventListener('click', function() {
  const nextSeq = String(<?= count($copies) + 1 ?>).padStart(2, '0');
  const code = 'CPY-<?= e($book['book_code']) ?>-' + nextSeq;
  const copyInput = document.getElementById('input-copy-code');
  const barcodeInput = document.getElementById('input-barcode');
  const shelfInput = document.getElementById('input-shelf');
  if (copyInput) copyInput.value = code;
  if (barcodeInput) barcodeInput.value = code;
  <?php 
    $lastShelf = '';
    foreach ($copies as $c) {
      if (!empty($c['shelf_location'])) { $lastShelf = $c['shelf_location']; break; }
    }
  ?>
  <?php if ($lastShelf): ?>
  if (shelfInput && !shelfInput.value) shelfInput.value = <?= json_encode($lastShelf) ?>;
  <?php endif; ?>
});
</script>

<section class="panel"><h2>Danh sách bản sao vật lý</h2><div class="table-scroll"><table><thead><tr><th>Mã bản sao / vị trí</th><th>Tình trạng</th><th>Lịch sử</th><th>Mã vạch & QR</th><th>Cập nhật</th></tr></thead><tbody>
<?php foreach($copies as $copy): ?><tr><td><strong><?= e($copy['copy_code']) ?></strong><small><?= e($copy['shelf_location'] ?: 'Chưa xếp kệ') ?></small><small><?= e($copy['barcode'] ?: '') ?></small></td><td><?= e($statuses[$copy['status']]) ?><small><?= e($conditions[$copy['item_condition']]) ?></small></td><td><?= (int)$copy['history_count'] ?> lượt mượn</td><td><a href="<?= e(url('/barcode/' . rawurlencode($copy['copy_code']))) ?>" target="_blank" style="font-size:0.8rem; text-decoration:underline;">Barcode</a> · <a href="<?= e(url('/qr/' . rawurlencode($copy['copy_code']))) ?>" target="_blank" style="font-size:0.8rem; text-decoration:underline;">QR</a></td><td>
<?php if(in_array($copy['status'],['on_loan','reserved'],true)): ?><span>Quản lý tại Mượn / Trả</span><?php else: ?>
<details><summary>Sửa bản sao</summary><form class="form-stack" method="post" action="<?= e(url('/admin/books/'.$book['id'].'/copies')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$copy['id'] ?>"><div class="copy-edit-grid"><label>Mã bản sao<input name="copy_code" maxlength="60" value="<?= e($copy['copy_code']) ?>" required></label><label>Mã vạch<input name="barcode" maxlength="80" value="<?= e($copy['barcode']) ?>"></label><label>Vị trí kệ<input name="shelf_location" maxlength="120" value="<?= e($copy['shelf_location']) ?>"></label><label>Tình trạng<select name="item_condition"><?php foreach($conditions as $key=>$label): ?><option value="<?= e($key) ?>"<?= selected($copy['item_condition'],$key) ?>><?= e($label) ?></option><?php endforeach; ?></select></label><label>Trạng thái<select name="status"><?php foreach(array_slice($statuses,0,4) as $key=>$label): ?><option value="<?= e($key) ?>"<?= selected($copy['status'],$key) ?>><?= e($label) ?></option><?php endforeach; ?></select></label></div><button class="button button-outline">Lưu bản sao</button></form></details>
<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
