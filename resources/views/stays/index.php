<?php
/** @var App\Models\Property[] $properties */
/** @var int $page */
use App\Core\View;
View::start('content');
?>
<div class="container">
  <div class="page-head">
    <h1 class="page-head__title">All stays</h1>
    <p class="text-muted">Every published stay on StayIn, across Tanzania.</p>
    <a href="/search" class="btn btn--ghost btn--sm"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Refine search</a>
  </div>
  <?php if (empty($properties)): ?>
  <div class="empty-state" style="text-align:center;padding:var(--space-8) 0;">
    <i class="fa-solid fa-hotel" style="font-size:3rem;color:var(--text-faint);margin-bottom:var(--space-4);" aria-hidden="true"></i>
    <h3>No stays yet</h3>
    <p class="text-muted">Check back soon — hosts are adding new listings across Tanzania.</p>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap:var(--space-4)">
    <?php foreach ($properties as $property): ?>
      <?= View::component('property-card', ['property' => $property]) ?>
    <?php endforeach; ?>
  </div>
  <?= View::partial('components/pagination', ['page' => $page, 'total' => count($properties) ?: 0, 'perPage' => 24, 'baseUrl' => '/stays']) ?>
  <?php endif; ?>
</div>
<?php View::stop() ?>
