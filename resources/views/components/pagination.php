<?php
/**
 * @var int $page
 * @var int $total
 * @var int $perPage
 * @var string $baseUrl
 */
$total = (int) ($total ?? 0);
$page = max(1, (int) ($page ?? 1));
$perPage = (int) ($perPage ?? 24);
$totalPages = max(1, (int) ceil($total / $perPage));
$base = rtrim($baseUrl ?? request()->url(), '/') . '/';
$start = max(1, $totalPages === 0 ? 0 : ($page - 5));
$end = min($totalPages, $page + 5);
if ($totalPages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Page navigation">
  <?php if ($page > 1): ?>
  <a href="<?= e($base . ($page - 1)) ?>" class="btn btn--ghost btn--sm pagination__prev" aria-label="Previous page"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></a>
  <?php endif; ?>
  <?php for ($i = $start; $i <= $end; $i++): ?>
  <?php if ($i === $page): ?>
  <span class="pagination__current" aria-current="page"><?= e($i) ?></span>
  <?php else: ?>
  <a href="<?= e($base . $i) ?>" class="btn btn--quiet btn--sm pagination__link" aria-label="Page <?= e($i) ?>"><?= e($i) ?></a>
  <?php endif; ?>
  <?php endfor; ?>
  <?php if ($page < $totalPages): ?>
  <a href="<?= e($base . ($page + 1)) ?>" class="btn btn--ghost btn--sm pagination__next" aria-label="Next page"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
  <?php endif; ?>
</nav>