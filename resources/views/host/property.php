<?php
/** @var array|null $property */
/** @var array $errors */
/** @var string $csrfToken */
use App\Core\View;
$isEdit = !empty($property);
View::start('content');
?>
<section class="page-section">
  <div class="container">
    <div class="host-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h1><?= $isEdit ? 'Edit property' : 'Add new property' ?></h1>
      <a href="/host/properties" class="btn btn--ghost"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert--error" role="alert">
        <ul style="margin:0;padding-left:var(--space-3);">
          <?php foreach ((array) $errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= $isEdit ? '/host/properties/' . e($property['id']) : '/host/properties' ?>"
      class="property-form" enctype="multipart/form-data" data-validate>
      <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="_method" value="PUT">
      <?php endif; ?>

      <div class="form-row"><input type="text" name="title" required placeholder="Property name" value="<?= e($property['title'] ?? '') ?>" class="input"></div>
      <div class="form-row"><textarea name="description" rows="4" placeholder="Description" class="input"><?= e($property['description'] ?? '') ?></textarea></div>

      <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
        <div class="form-row"><input type="text" name="city" required placeholder="City" value="<?= e($property['city'] ?? '') ?>" class="input"></div>
        <div class="form-row"><input type="text" name="region" placeholder="Region" value="<?= e($property['region'] ?? '') ?>" class="input"></div>
        <div class="form-row"><input type="number" name="price_per_night" required min="0" placeholder="Price per night (Tshs)" value="<?= e($property['price_per_night'] ?? '') ?>" class="input"></div>
        <div class="form-row"><input type="number" name="bedrooms" min="0" placeholder="Bedrooms" value="<?= e($property['bedrooms'] ?? 1) ?>" class="input"></div>
        <div class="form-row"><input type="number" name="bathrooms" min="0" placeholder="Bathrooms" value="<?= e($property['bathrooms'] ?? 1) ?>" class="input"></div>
        <div class="form-row"><input type="number" name="sleeps" min="1" placeholder="Sleeps" value="<?= e($property['sleeps'] ?? 1) ?>" class="input"></div>
      </div>

      <div class="form-row">
        <label style="display:block;margin-bottom:var(--space-1);font-weight:600;">Photos</label>
        <input type="file" name="images[]" accept="image/*" multiple>
      </div>

      <button type="submit" class="btn btn--primary">
        <i class="fa-solid fa-save" aria-hidden="true"></i> <?= $isEdit ? 'Update property' : 'Create property' ?>
      </button>
    </form>
  </div>
</section>
<?php View::stop() ?>
