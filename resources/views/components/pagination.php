<?php
/**
 * @var int $page @var int $total @var int $perPage @var string $baseUrl
 */
$total = (int) ($total ?? 0);
$page = max(1, (int) ($page ?? 1));
$perPage = (int) ($perPage ?? 24);
$totalPages = max(1, (int) ceil($total / $perPage));
$pageUrl = static fn (int $number): string => url(($baseUrl ?? '/search') . '?' . http_build_query(array_merge($query ?? [], ['page' => $number])));
$start = max(1, $totalPages === 0 ? 0 : ($page - 5));
$end = min($totalPages, $page + 5);
if ($totalPages <= 1) {
    return;
}
?>
<nav class="si-pagination" aria-label="Page navigation">
  <?php if ($page > 1): ?>
  <a href="<?= e($pageUrl($page - 1)) ?>" class="si-pagination__link" aria-label="Previous page"><?= icon('chevron-left', 'si-icon--sm') ?><span class="sr-only">Previous page</span></a>
  <?php endif; ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
  <?php if ($i === $page): ?>
  <span class="si-pagination__span" aria-current="page"><?= e($i) ?></span>
  <?php else: ?>
  <a href="<?= e($pageUrl($i)) ?>" class="si-pagination__link" aria-label="Page <?= e($i) ?>"><?= e($i) ?></a>
  <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
  <a href="<?= e($pageUrl($page + 1)) ?>" class="si-pagination__link" aria-label="Next page"><?= icon('chevron-right', 'si-icon--sm') ?><span class="sr-only">Next page</span></a>
  <?php endif; ?>
</nav>
