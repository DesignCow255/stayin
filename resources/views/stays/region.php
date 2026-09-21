<?php
/** @var App\Models\Property[] $properties */
/** @var string $region */
/** @var int $page */
use App\Core\View;
\App\Core\View::start('content');
?>
<div class="container">
  <div class="page-head"><p class="eyebrow">Discover Tanzania</p>
    <h1 class="page-head__title">Stays in <?= e(ucwords($region)) ?></h1>
    <p class="text-muted">Properties available in <?= e(ucwords($region)) ?>, Tanzania.</p>
  </div>
  <?php if (empty($properties)): ?>
  <div class="empty-state" style="text-align:center;padding:var(--space-8) 0;">
    <i class="fa-solid fa-location-dot" style="font-size:3rem;color:var(--text-faint);margin-bottom:var(--space-4);" aria-hidden="true"></i>
    <h3>No stays in this region yet</h3>
    <p class="text-muted">Be the first to list a property in <?= e(ucwords($region)) ?> — <a href="<?= e(url('/register?role=host')) ?>">become a host</a>.</p>
  </div>
  <?php else: ?>
  <div class="grid grid--cards">
    <?php foreach ($properties as $property): ?>
      <?= \App\Core\View::component('property-card', ['property' => $property]) ?>
    <?php endforeach; ?>
  </div>
  <?= \App\Core\View::partial('components/pagination', ['page' => $page, 'total' => $count, 'perPage' => 24, 'baseUrl' => '/stays/' . rawurlencode($regionSlug)]) ?>
  <?php endif; ?>
</div>
<?php \App\Core\View::stop() ?>
