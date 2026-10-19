<?php if (isset($pagination)): ?>
<nav class="pagination" aria-label="Phân trang <?= e($pagination->pageParameter) ?>">
<span><?= (int) $pagination->total ?> kết quả · Trang <?= (int) $pagination->page ?>/<?= (int) $pagination->pages ?></span>
<?php 
$baseQuery = array_filter($_GET, static fn($value) => is_scalar($value) && $value !== '');
for ($number = max(1, $pagination->page - 2); $number <= min($pagination->pages, $pagination->page + 2); $number++):
  $params = $baseQuery; 
  $params[$pagination->pageParameter] = $number;
?>
<?php if ($number === $pagination->page): ?><span aria-current="page" class="button"><?= (int) $number ?></span><?php else: ?><a class="button button-outline" aria-label="Trang <?= (int) $number ?>" href="?<?= e(http_build_query($params)) ?><?= isset($paginationAnchor) ? '#' . e($paginationAnchor) : '' ?>"><?= (int) $number ?></a><?php endif; ?>
<?php endfor; ?>
<?php foreach ([$pagination->page - 1 => '← Trước', $pagination->page + 1 => 'Sau →'] as $number => $label): if ($number < 1 || $number > $pagination->pages) continue;
$params = $baseQuery; $params[$pagination->pageParameter] = $number; ?>
<a class="button button-outline" href="?<?= e(http_build_query($params)) ?><?= isset($paginationAnchor) ? '#' . e($paginationAnchor) : '' ?>"><?= e($label) ?></a>
<?php endforeach; ?></nav><?php endif; ?>
