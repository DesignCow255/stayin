<?php
/** @var array $property @var array $roomTypes @var array $images @var array $reviews @var array $similar @var array|null $host */
use App\Core\View;

View::start('content');
$amenities = amenity_items($property['amenities'] ?? null);
$isVerified = ($property['verification_status'] ?? '') === 'verified';
$hostName = trim((string) (($host['first_name'] ?? '') . ' ' . ($host['last_name'] ?? '')));
$hostInitials = mb_strtoupper(mb_substr((string) ($host['first_name'] ?? 'H'), 0, 1) . mb_substr((string) ($host['last_name'] ?? ($host['first_name'] ?? '')), 0, 1));
$propertyId = (int) $property['id'];
$rating = (float) ($property['rating'] ?? 0);
$reviewCount = (int) ($property['review_count'] ?? 0);
$latitude = isset($property['latitude']) && $property['latitude'] !== null ? (float) $property['latitude'] : null;
$longitude = isset($property['longitude']) && $property['longitude'] !== null ? (float) $property['longitude'] : null;
$mapsUrl = $latitude !== null && $longitude !== null
    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($latitude . ',' . $longitude)
    : null;
$shareUrl = url('/property/' . $property['slug']);
$galleryFallback = stayin_image_asset(trim((string) ($property['region'] ?? '') . ' ' . (string) ($property['property_type'] ?? '') . ' ' . (string) ($property['name'] ?? '')));
?>
<article class="si-container property-show"<?= track('listing_view', ['entity_id' => $propertyId, 'surface' => 'property_detail']) ?>>
  <nav class="breadcrumb" aria-label="Breadcrumb" style="margin-block-end:var(--si-space-4)">
    <a href="<?= e(url('/stays')) ?>">All stays</a>
    <span aria-hidden="true">/</span>
    <a href="<?= e(region_url((string) $property['region'])) ?>"><?= e($property['region']) ?></a>
    <span aria-hidden="true">/</span>
    <span aria-current="page"><?= e($property['name']) ?></span>
  </nav>

  <header class="si-stack" style="gap:var(--si-space-2);margin-block-end:var(--si-space-5)">
    <p class="si-eyebrow"><?= e(property_type_label((string) $property['property_type'])) ?> · <?= e($property['region']) ?></p>
    <h1 class="si-title si-title--xl"><?= e($property['name']) ?></h1>
    <div class="si-row" style="gap:var(--si-space-3)">
      <span class="si-card__loc"><?= icon('map-pin', 'si-icon--sm') ?><?= e(implode(', ', array_filter([(string) ($property['address'] ?? ''), (string) ($property['district'] ?? ''), (string) $property['region']]))) ?></span>
      <?php if ($isVerified): ?>
      <span class="si-badge si-badge--verified"><?= icon('badge-check', 'si-icon--sm') ?> Verified property</span>
      <?php endif; ?>
      <?php if ($rating > 0): ?>
      <span class="si-rating"><?= icon('star-filled', 'si-icon--sm') ?><span class="si-amount"><?= e(number_format($rating, 1)) ?></span><?php if ($reviewCount > 0): ?><span class="si-rating__count">· <?= e($reviewCount) ?> reviews</span><?php endif; ?></span>
      <?php endif; ?>
    </div>
  </header>

  <!-- Gallery -->
  <section class="si-gallery" id="photos" aria-label="Photographs of <?= e($property['name']) ?>">
    <?php if ($images): ?>
      <a class="si-gallery__main" href="<?= e(image_url($images[0]['file_url'])) ?>" data-gallery-image data-gallery-index="0" data-gallery-caption="<?= e($images[0]['caption'] ?? $property['name']) ?>">
        <img src="<?= e(image_url($images[0]['file_url'])) ?>" alt="<?= e($images[0]['caption'] ?? $property['name']) ?>" width="1600" height="1000" fetchpriority="high" decoding="async">
      </a>
      <div class="si-gallery__side">
        <?php foreach (array_slice($images, 1, 2) as $i => $image): ?>
        <a class="si-gallery__thumb" href="<?= e(image_url($image['file_url'])) ?>" data-gallery-image data-gallery-index="<?= e((string) ($i + 1)) ?>" data-gallery-caption="<?= e($image['caption'] ?? $property['name']) ?>">
          <img src="<?= e(image_url($image['file_url'])) ?>" alt="<?= e($image['caption'] ?? $property['name']) ?>" loading="lazy" decoding="async" width="600" height="450">
        </a>
        <?php endforeach; ?>
      </div>
      <?php if (count($images) > 3): ?>
      <button type="button" class="si-btn si-btn--inverse si-gallery__all" data-dialog-open="photoDialog"<?= track('listing_gallery_open', ['entity_id' => $propertyId, 'surface' => 'gallery']) ?>><?= icon('images') ?> View all <?= e(count($images)) ?> photographs</button>
      <?php endif; ?>
    <?php else: ?>
      <a class="si-gallery__main" href="<?= e(asset($galleryFallback)) ?>" data-gallery-image data-gallery-index="0" data-gallery-caption="<?= e($property['name']) ?>">
        <img src="<?= e(asset($galleryFallback)) ?>" alt="<?= e($property['name']) ?>" width="1600" height="1000" fetchpriority="high" decoding="async">
      </a>
      <div class="si-gallery__side">
        <?php foreach (['zanzibar', 'serengeti'] as $i => $fallbackContext): ?>
        <?php $sideFallback = stayin_image_asset($fallbackContext . ' ' . (string) ($property['region'] ?? '')); ?>
        <a class="si-gallery__thumb" href="<?= e(asset($sideFallback)) ?>" data-gallery-image data-gallery-index="<?= e((string) ($i + 1)) ?>" data-gallery-caption="<?= e($property['name']) ?>">
          <img src="<?= e(asset($sideFallback)) ?>" alt="<?= e($property['name']) ?>" loading="lazy" decoding="async" width="600" height="450">
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Sticky section nav -->
  <nav class="si-subnav" aria-label="Property sections">
    <a href="#overview">Overview</a>
    <a href="#rooms">Rooms</a>
    <?php if ($reviews): ?><a href="#reviews">Reviews</a><?php endif; ?>
    <a href="#location">Location</a>
    <a href="#policies">Policies</a>
    <?php if ($similar): ?><a href="#similar">Similar stays</a><?php endif; ?>
  </nav>

  <!-- Affiliate / referral panel -->
  <section id="refer" class="si-detail__section si-detail__referral">
    <div class="si-surface si-surface--pad">
      <div class="si-row" style="justify-content:space-between;align-items:center;gap:var(--si-space-3)">
        <div>
          <p class="si-eyebrow">Partner programme</p>
          <h3 class="si-title si-title--sm" style="margin:0">Earn a commission for every booking you send</h3>
          <p class="si-caption" style="margin:0">Share this property with your audience. When a traveller books through your link, you earn a referral commission on every paid stay — tracked automatically.</p>
        </div>
        <?php
        $affiliateCode = (string) (is_array($authUser ?? null) ? ($authUser['referral_code'] ?? ($authUser['id'] ?? '0')) : '0');
        $locale = app_locale();
        $affUrl = \App\Models\Property::affiliateLink($propertyId, $affiliateCode, $locale);
        ?>
        <div class="si-row" style="gap:var(--si-space-2);align-items:center">
          <a class="si-link" href="mailto:?subject=<?= e(rawurlencode('Referral link — ' . $property['name'])) ?>&body=<?= e(rawurlencode($affUrl)) ?>"><?= icon('mail', 'si-icon--sm') ?> Email</a>
          <a class="si-link" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?= e(rawurlencode(strip_tags($property['name']) . ' — ' . $affUrl)) ?>"><?= icon('whatsapp', 'si-icon--sm') ?> WhatsApp</a>
          <button type="button" class="si-btn si-btn--outline si-btn--sm" data-copy="<?= e($affUrl) ?>"><?= icon('copy', 'si-icon--sm') ?> Copy link</button>
        </div>
      </div>
    </div>
  </section>

  <div class="si-detail">
    <div class="si-detail__main">
      <section id="overview" class="si-detail__section">
        <p class="si-eyebrow">The property</p>
        <h2>About this stay</h2>
        <p class="si-lede si-pretty"><?= e($property['description_en'] ?: 'Contact us for more information about this property.') ?></p>
        <?php if (!empty($property['description_sw'])): ?>
        <p class="si-caption si-pretty" style="margin-block-start:var(--si-space-3)" lang="sw"><?= e($property['description_sw']) ?></p>
        <?php endif; ?>
        <?php if ($amenities): ?>
        <h3>What this place offers</h3>
        <ul class="si-amenities">
          <?php foreach ($amenities as $amenity): ?>
          <li><?= icon($amenity['icon']) ?><?= e($amenity['label']) ?></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </section>

      <section id="rooms" class="si-detail__section">
        <p class="si-eyebrow">Make yourself at home</p>
        <h2>Room options</h2>
        <div class="si-stack">
          <?php foreach ($roomTypes as $room): ?>
          <article class="si-room">
            <div>
              <h3 class="si-room__name"><?= e($room['name']) ?></h3>
              <?php if (!empty($room['description'])): ?><p class="si-room__desc"><?= e($room['description']) ?></p><?php endif; ?>
              <p class="si-caption" style="margin:0">
                <?= icon('users', 'si-icon--sm') ?> Up to <?= e($room['max_guests']) ?> guests
                <?php if (!empty($room['bed_configuration'])): ?> · <?= icon('bed', 'si-icon--sm') ?> <?= e($room['bed_configuration']) ?><?php endif; ?>
                <?php if ((int) $room['quantity'] > 1): ?> · <?= e($room['quantity']) ?> rooms of this type<?php endif; ?>
              </p>
            </div>
            <div class="si-stack si-stack--sm" style="text-align:end">
              <p class="si-room__price" style="margin:0"><?= e(format_money((float) $room['base_price'], (string) $room['currency'])) ?></p>
              <p class="si-caption" style="margin:0">per night · <?= e($room['currency']) ?></p>
              <a class="si-link si-link--muted" style="justify-content:flex-end" href="<?= e(url('/property/' . $property['slug'] . '?' . http_build_query(['room_id' => $room['id']]) ) . '#availability') ?>">Choose room<?= icon('arrow-right', 'si-icon--sm') ?></a>
            </div>
          </article>
          <?php endforeach; ?>
          <?php if (!$roomTypes): ?>
          <p class="si-caption">No rooms are currently listed for booking.</p>
          <?php endif; ?>
        </div>
      </section>


      <?php if ($reviews): ?>
      <section id="reviews" class="si-detail__section">
        <p class="si-eyebrow">Guest perspectives</p>
        <h2>Reviews from real stays</h2>
        <div class="si-stack">
          <?php foreach ($reviews as $review): ?>
          <article class="si-review">
            <div class="si-review__head">
              <span class="si-avatar si-avatar--muted" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $review['first_name'], 0, 1))) ?></span>
              <span class="si-review__name"><?= e(trim($review['first_name'] . ' ' . ($review['last_name'] ?? ''))) ?></span>
              <span class="si-rating" style="margin-inline-start:auto"><?= icon('star-filled', 'si-icon--sm') ?><span class="si-amount"><?= e(number_format((float) $review['rating'], 1)) ?></span></span>
            </div>
            <?php if (!empty($review['comment'])): ?><p class="si-review__body"><?= e($review['comment']) ?></p><?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <section id="location" class="si-detail__section">
        <p class="si-eyebrow">Your surroundings</p>
        <h2>Location</h2>
        <p class="si-lede"><?= e($property['address'] ?: $property['region']) ?></p>
        <?php if ($mapsUrl): ?>
        <div class="si-row" style="margin-block-start:var(--si-space-4)"<?= track('listing_map_click', ['entity_id' => $propertyId, 'surface' => 'location']) ?>>
          <a class="si-btn si-btn--outline" href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener noreferrer"><?= icon('map-pin') ?> Open location in maps</a>
          <?php if (!empty($property['landmark'])): ?><span class="si-caption">Near <?= e($property['landmark']) ?></span><?php endif; ?>
        </div>
        <?php else: ?>
        <p class="si-hint">Exact coordinates have not been published for this property.</p>
        <?php endif; ?>
      </section>

      <section id="policies" class="si-detail__section">
        <p class="si-eyebrow">Before you reserve</p>
        <h2>Policies</h2>
        <div class="si-stack si-stack--sm">
          <p class="si-caption" style="margin:0"><?= icon('clock', 'si-icon--sm') ?> Check in from <?= e(substr((string) $property['check_in_time'], 0, 5)) ?> · Check out by <?= e(substr((string) $property['check_out_time'], 0, 5)) ?></p>
          <?php if (!empty($property['house_rules'])): ?>
          <h3 style="margin-block-start:var(--si-space-4)">House rules</h3>
          <p class="si-lede si-pretty"><?= e($property['house_rules']) ?></p>
          <?php endif; ?>
          <h3 style="margin-block-start:var(--si-space-4)">Cancellation</h3>
          <p class="si-lede si-pretty"><?= e($property['cancellation_policy'] ?: 'Contact the host for cancellation details before booking.') ?></p>
        </div>
      </section>

      <?php if ($similar): ?>
      <section id="similar" class="si-detail__section">
        <div class="si-section-head">
          <div class="si-section-head__text">
            <p class="si-eyebrow">Keep exploring</p>
            <h2>Similar stays in <?= e($property['region']) ?></h2>
          </div>
          <a class="si-link" href="<?= e(region_url((string) $property['region'])) ?>">All in <?= e($property['region']) ?><?= icon('arrow-right', 'si-icon--sm') ?></a>
        </div>
        <div class="si-grid-cards">
          <?php foreach ($similar as $item): ?>
          <?= View::component('property-card', ['property' => $item]) ?>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>
    </div>

    <aside class="si-detail__aside" aria-label="Availability and booking">
      <?= View::partial('partials/booking-card', ['property' => $property, 'roomTypes' => $roomTypes, 'selection' => $selection, 'quote' => $quote, 'quoteError' => $quoteError, 'host' => $host]) ?>

      <?php if ($hostName !== ''): ?>
      <div class="si-host" style="margin-block-start:var(--si-space-4)">
        <span class="si-avatar" aria-hidden="true"><?= e($hostInitials) ?></span>
        <div>
          <p class="si-host__name"><?= e($hostName) ?></p>
          <p class="si-host__meta">Your host<?php if (!empty($host['email_verified_at'])): ?> · email verified<?php endif; ?></p>
        </div>
      </div>
      <?php endif; ?>
    </aside>
  </div>

  <a class="si-btn si-btn--primary mobile-booking" href="#availability">Check availability<?= icon('arrow-right', 'si-icon--sm') ?></a>
</article>

<dialog class="si-dialog" id="photoDialog" aria-label="Property photograph">
  <button type="button" class="si-btn si-btn--inverse si-btn--icon si-dialog__close" data-dialog-close aria-label="Close photograph"><?= icon('close') ?></button>
  <img src="" alt="">
</dialog>

<dialog class="si-dialog" id="shareDialog" aria-label="Share this stay">
  <div class="si-share">
    <h2>Share “<?= e($property['name']) ?>”</h2>
    <div class="si-share__options">
      <button type="button" class="si-share__option" data-copy="<?= e($shareUrl) ?>"><?= icon('copy') ?><span data-copy-label>Copy link</span></button>
      <a class="si-share__option" href="mailto:?subject=<?= e(rawurlencode($property['name'] . ' on StayIn')) ?>&body=<?= e(rawurlencode('Take a look at this stay: ' . $shareUrl)) ?>"><?= icon('mail') ?>Email</a>
      <a class="si-share__option" target="_blank" rel="noopener noreferrer" href="https://wa.me/?text=<?= e(rawurlencode($property['name'] . ' — ' . $shareUrl)) ?>"><?= icon('whatsapp') ?>WhatsApp</a>
    </div>
    <button type="button" class="si-btn si-btn--outline si-btn--block" data-dialog-close>Close</button>
  </div>
</dialog>
<?php View::stop(); ?>

