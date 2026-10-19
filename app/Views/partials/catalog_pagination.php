<?php
/** Catalog-only pagination: preserves valid filters, leaves other pages untouched. */
if (!isset($pagination) || (int) $pagination->pages < 2) {
    return;
}
$currentPage = max(1, (int) $pagination->page);
$totalPages = max(1, (int) $pagination->pages);
$perPage = max(1, (int) $pagination->perPage);
$totalBooks = max(0, (int) $pagination->total);
$pageField = (string) $pagination->pageParameter;
$filterKeys = ['q', 'category', 'publisher', 'year', 'availability', 'sort', 'view'];
$filters = [];
foreach ($filterKeys as $key) {
    $value = $_GET[$key] ?? null;
    if (is_string($value) && strlen($value) <= 240) {
        $filters[$key] = $value;
    }
}
$paginationAction = $paginationAction ?? url('/catalog');
$paginationAnchor = $paginationAnchor ?? 'catalog-results';
$catalogPageUrl = static function (int $target) use ($filters, $pageField, $totalPages, $paginationAction, $paginationAnchor): string {
    $params = $filters;
    $params[$pageField] = min($totalPages, max(1, $target));
    $hash = $paginationAnchor !== '' ? '#' . $paginationAnchor : '';
    return $paginationAction . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986) . $hash;
};
$firstResult = (($currentPage - 1) * $perPage) + 1;
$lastResult = min($totalBooks, $currentPage * $perPage);
$windowStart = max(1, $currentPage - 2);
$windowEnd = min($totalPages, $currentPage + 2);
?>
<nav class="ph-catalog-pagination" aria-label="Điều hướng trang danh mục sách">
  <p class="ph-catalog-pagination-summary" role="status">
    Hiển thị <strong><?= $firstResult ?>–<?= $lastResult ?></strong> / <?= $totalBooks ?> sách
    <span class="ph-catalog-page-info">· Trang <strong><?= $currentPage ?></strong>/<?= $totalPages ?></span>
  </p>
  <div class="ph-catalog-pagination-pages">
    <?php if ($currentPage > 1): ?>
      <a class="ph-page-link ph-page-edge" href="<?= e($catalogPageUrl(1)) ?>" aria-label="Đến trang đầu">« Đầu</a>
      <a class="ph-page-link" href="<?= e($catalogPageUrl($currentPage - 1)) ?>" rel="prev">‹ Trước</a>
    <?php else: ?>
      <span class="ph-page-link is-disabled" aria-disabled="true">« Đầu</span>
      <span class="ph-page-link is-disabled" aria-disabled="true">‹ Trước</span>
    <?php endif; ?>
    <?php if ($windowStart > 1): ?>
      <a class="ph-page-link" href="<?= e($catalogPageUrl(1)) ?>" aria-label="Trang 1">1</a>
      <?php if ($windowStart > 2): ?><span class="ph-page-ellipsis" aria-hidden="true">…</span><?php endif; ?>
    <?php endif; ?>
    <?php for ($number = $windowStart; $number <= $windowEnd; $number++): ?>
      <?php if ($number === $currentPage): ?>
        <span class="ph-page-link is-current" aria-current="page" aria-label="Trang <?= $number ?>, trang hiện tại"><?= $number ?></span>
      <?php else: ?>
        <a class="ph-page-link" href="<?= e($catalogPageUrl($number)) ?>" aria-label="Trang <?= $number ?>"><?= $number ?></a>
      <?php endif; ?>
    <?php endfor; ?>
    <?php if ($windowEnd < $totalPages): ?>
      <?php if ($windowEnd < $totalPages - 1): ?><span class="ph-page-ellipsis" aria-hidden="true">…</span><?php endif; ?>
      <a class="ph-page-link" href="<?= e($catalogPageUrl($totalPages)) ?>" aria-label="Trang <?= $totalPages ?>"><?= $totalPages ?></a>
    <?php endif; ?>
    <?php if ($currentPage < $totalPages): ?>
      <a class="ph-page-link" href="<?= e($catalogPageUrl($currentPage + 1)) ?>" rel="next">Sau ›</a>
      <a class="ph-page-link ph-page-edge" href="<?= e($catalogPageUrl($totalPages)) ?>" aria-label="Đến trang cuối">Cuối »</a>
    <?php else: ?>
      <span class="ph-page-link is-disabled" aria-disabled="true">Sau ›</span>
      <span class="ph-page-link is-disabled" aria-disabled="true">Cuối »</span>
    <?php endif; ?>
  </div>
  <form class="ph-catalog-pagination-jump" method="get" action="<?= e($paginationAction) ?><?= $paginationAnchor !== '' ? '#' . e($paginationAnchor) : '' ?>">
    <?php foreach ($filters as $filterName => $filterValue): ?>
      <input type="hidden" name="<?= e($filterName) ?>" value="<?= e($filterValue) ?>">
    <?php endforeach; ?>
    <label>Đến trang
      <input name="<?= e($pageField) ?>" type="number" min="1" max="<?= $totalPages ?>" value="<?= $currentPage ?>" inputmode="numeric" required aria-label="Nhập số trang (1 đến <?= $totalPages ?>)">
    </label>
    <button type="submit" class="ph-page-go">Đi</button>
  </form>
</nav>
