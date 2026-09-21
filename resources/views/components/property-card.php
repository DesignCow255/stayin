<?php
/**
 * Premium property card (§10).
 *
 * @var array{
 *   id: int,
 *   name: string,
 *   slug: string,
 *   region: string,
 *   rating?: float|null,
 *   review_count?: int,
 *   cover_image?: string|null,
 *   min_price?: float|int|null,
 *   currency?: string,
 *   featured?: int|bool,
 *   verification_status?: string|bool,
 *   property_type?: string,
 * } $property
 */
$cover = $property['cover_image'] ?? null;
$url = url('/property/' . $property['slug']);
$region = (string) ($property['region'] ?? '');
$propertyType = (string) ($property['property_type'] ?? '');
$fallbackImage = stayin_image_asset(trim($region . ' ' . $propertyType . ' ' . (string) ($property['name'] ?? '')));
$hasPrice = isset($property['min_price']) && $property['min_price'] !== null && (float) $property['min_price'] > 0;
$minPrice = $hasPrice ? format_money((float) $property['min_price'], $property['currency'] ?? 'TZS') : '';
$rating = $property['rating'] ?? null;
$reviewCount = (int) ($property['review_count'] ?? 0);
$verified = !empty($property['verification_status']) && $property['verification_status'] === 'verified';
$featured = !empty($property['featured']) && (int) $property['featured'] !== 0;
$saved = is_favourite((int) ($property['id'] ?? 0));
$propertyId = (int) ($property['id'] ?? 0);
?>
<article class="si-card si-reveal">
  <div class="si-card__media">
    <a href="<?= e($url) ?>" aria-label="<?= e($property['name'] ?? 'Stay') ?>">
      <img class="si-card__img" src="<?= e(image_url($cover, $fallbackImage)) ?>"
           alt="<?= e($property['name'] ?? 'Stay') ?>" loading="lazy" decoding="async" width="800" height="600">
    </a>
    <div class="si-card__flags">
      <div class="si-row si-row--nowrap" style="gap:6px">
        <?php if ($featured): ?>
        <span class="si-badge si-badge--over"><?= icon('sparkles', 'si-icon--sm') ?> Featured</span>
        <?php endif; ?>
        <?php if ($verified): ?>
        <span class="si-badge si-badge--over"><?= icon('badge-check', 'si-icon--sm') ?> Verified</span>
        <?php endif; ?>
      </div>
      <?php if ($propertyId > 0): ?>
      <?php if (auth_check()): ?>
      <form method="POST" action="<?= e(url('/guest/favourites')) ?>"<?= track('favourite_toggled', ['entity_id' => $propertyId, 'surface' => 'card']) ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="property_id" value="<?= e($propertyId) ?>">
        <input type="hidden" name="action" value="<?= $saved ? 'remove' : 'add' ?>">
        <button type="submit" class="si-fav" data-favourite-toggle
                aria-pressed="<?= $saved ? 'true' : 'false' ?>"
                aria-label="<?= $saved ? 'Remove from saved stays' : 'Save this stay' ?>">
          <?= icon($saved ? 'heart-filled' : 'heart') ?>
        </button>
      </form>
      <?php else: ?>
      <a href="<?= e(url('/login')) ?>" class="si-fav" aria-label="Sign in to save this stay"><?= icon('heart') ?></a>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="si-card__body">
    <p class="si-card__loc"><?= icon('map-pin', 'si-icon--sm') ?><?= e($region) ?></p>
    <h3 class="si-card__title"><a href="<?= e($url) ?>"><?= e($property['name'] ?? '') ?></a></h3>
    <div class="si-card__specs">
      <?php if ($propertyType !== ''): ?>
      <span class="si-card__spec"><?= icon(\App\Support\PropertyType::icon($propertyType), 'si-icon--sm') ?><?= e(property_type_label($propertyType)) ?></span>
      <?php endif; ?>
      <?php if ($rating !== null && $rating !== '' && (float) $rating > 0): ?>
      <span class="si-card__spec si-rating"><?= icon('star-filled', 'si-icon--sm') ?><span class="si-amount"><?= e(number_format((float) $rating, 1)) ?></span><?php if ($reviewCount > 0): ?><span class="si-rating__count">(<?= e($reviewCount) ?>)</span><?php endif; ?></span>
      <?php endif; ?>
    </div>
    <div class="si-card__foot">
      <?php if ($hasPrice): ?>
      <p class="si-card__price" style="margin:0"><?= e($minPrice) ?> <span class="si-card__per">/ night</span></p>
      <a class="si-link si-link--muted" href="<?= e($url) ?>">View<?= icon('arrow-right', 'si-icon--sm') ?></a>
      <?php else: ?>
      <p class="si-card__price si-card__per" style="margin:0">Prices on request</p>
      <a class="si-link si-link--muted" href="<?= e($url) ?>">View<?= icon('arrow-right', 'si-icon--sm') ?></a>
      <?php endif; ?>
    </div>
  </div>
</article>
