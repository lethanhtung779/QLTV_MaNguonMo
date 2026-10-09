<?php if (isset($pagination)): ?>
<nav class="pagination" aria-label="Phân trang">
<span><?= (int) $pagination->total ?> kết quả · Trang <?= (int) $pagination->page ?>/<?= (int) $pagination->pages ?></span>
<?php foreach ([$pagination->page - 1 => '← Trước', $pagination->page + 1 => 'Sau →'] as $number => $label): if ($number < 1 || $number > $pagination->pages) continue;
$params = array_filter($_GET, static fn($value) => is_scalar($value)); $params['page'] = $number; ?>
<a class="button button-outline" href="?<?= e(http_build_query($params)) ?>"><?= e($label) ?></a>
<?php endforeach; ?></nav><?php endif; ?>
