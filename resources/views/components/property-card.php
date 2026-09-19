<?php
/**
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
$url = url('/property/' . e($property['slug']));
$region = $property['region'] ?? '';
$propertyType = $property['property_type'] ?? '';
$minPrice = isset($property['min_price']) ? format_money((float) ($property['min_price'] ?? 0), $property['currency'] ?? 'TZS') : '';
$rating = $property['rating'] ?? null;
$reviewCount = (int) ($property['review_count'] ?? 0);
$verified = !empty($property['verification_status']) && $property['verification_status'] === 'verified';
$featured = !empty($property['featured']) && (int) $property['featured'] !== 0;
?>
<a href="<?= e($url) ?>" class="property-card">
  <div class="property-card__media">
    <img src="<?= e(image_url($cover, 'assets/images/placeholder-stay.svg')) ?>" alt="<?= e($property['name'] ?? 'Stay') ?>" loading="lazy" class="property-card__image">
    <?php if ($featured): ?>
    <span class="badge badge--brand property-card__badge"><?= e(t('featured')) ?: 'Featured' ?></span>
    <?php endif; ?>
    <?php if ($propertyType): ?>
    <span class="property-card__type"><?= e(ucfirst($propertyType)) ?></span>
    <?php endif; ?>
  </div>
  <div class="property-card__body">
    <div class="property-card__meta">
      <?php if ($verified): ?>
      <span class="badge badge--success property-card__badge"><i class="fa-solid fa-check" aria-hidden="true"></i> Verified</span>
      <?php endif; ?>
      <span class="property-card__region"><?= e($region) ?></span>
    </div>
    <h3 class="property-card__title"><?= e($property['name'] ?? '') ?></h3>
    <?php if ($rating !== null && $rating !== '' && $rating > 0): ?>
    <div class="property-card__rating">
      <i class="fa-solid fa-star" style="color:var(--brand)" aria-hidden="true"></i>
      <strong><?= e(number_format((float) $rating, 1)) ?></strong>
      <span class="text-muted">(<?= e($reviewCount) ?> <?= e(str_contains(t('reviews') ?: 'reviews', 'review') ? 'review' : 'reviews') ?>)</span>
    </div>
    <?php endif; ?>
    <?php if ($minPrice !== ''): ?>
    <div class="property-card__price">
      <span class="property-card__price-value"><?= e($minPrice) ?></span>
      <span class="property-card__price-note">starting · <?= e(strtoupper($property['currency'] ?? 'TZS')) ?></span>
    </div>
    <?php endif; ?>
  </div>
</a>