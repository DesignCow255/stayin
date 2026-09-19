<?php
/** @var App\Models\Property $property */
/** @var string|null $currency */
/** @var int $nights */
/** @var array<string,mixed> $availability */
use App\Core\View;
View::start('content');
?>

<article class="property-show">
  <div class="property-gallery" style="position:relative;">
    <?php if (!empty($property['images'])): ?>
      <img src="<?= e($property['images'][0]['url']) ?>" alt="<?= e($property['title']) ?>"
        class="property-gallery__hero" loading="lazy">
    <?php else: ?>
      <div class="property-gallery__hero" style="background:var(--surface-2);display:flex;align-items:center;justify-content:center;">
        <i class="fa-solid fa-house" style="font-size:4rem;color:var(--text-faint);"></i>
      </div>
    <?php endif; ?>
    <?php if (isset($property['is_verified']) && $property['is_verified']): ?>
      <span class="badge badge--verified" style="position:absolute;top:var(--space-3);right:var(--space-3);">
        <i class="fa-solid fa-badge-check" aria-hidden="true"></i> Verified
      </span>
    <?php endif; ?>
  </div>

  <div class="container" style="padding-top:var(--space-6);">
    <div class="property-layout">
      <!-- Main content -->
      <main class="property-main">
        <header class="property-header">
          <h1 class="property-title"><?= e($property['title']) ?></h1>
          <p class="property-location">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <?= e($property['city'] ?? '') ?>, <?= e($property['region'] ?? '') ?>, <?= e($property['country'] ?? 'Tanzania') ?>
          </p>
          <?php if (!empty($property['rating'])): ?>
            <div class="rating" style="display:flex;align-items:center;gap:var(--space-1);">
              <span class="rating__score"><?= e($property['rating']) ?></span>
              <i class="fa-solid fa-star" style="color:var(--rating);"></i>
              <span class="text-muted">(<?= e($property['reviews_count'] ?? 0) ?> reviews)</span>
            </div>
          <?php endif; ?>

          <div class="property-price" style="margin-top:var(--space-3);">
            <?php if (!empty($property['price_per_night'])): ?>
              <span class="price-amount">Tshs <?= e(number_format((float) $property['price_per_night'], 0, '.', ',')) ?></span>
              <span class="text-muted">per night</span>
            <?php endif; ?>
          </div>
        </header>

        <nav class="property-nav" style="display:flex;gap:var(--space-4);border-bottom:1px solid var(--border);margin:var(--space-4) 0;padding-bottom:var(--space-2);">
          <a href="#overview" class="property-nav__link">Overview</a>
          <a href="#photos" class="property-nav__link">Photos</a>
          <a href="#reviews" class="property-nav__link">Reviews</a>
          <a href="#location" class="property-nav__link">Location</a>
        </nav>

        <section id="overview" class="property-section">
          <h2>About this stay</h2>
          <p class="property-description"><?= e($property['description'] ?? 'No description available.') ?></p>

          <?php $bedrooms = (int) ($property['bedrooms'] ?? 0); ?>
          <?php $bathrooms = (int) ($property['bathrooms'] ?? 0); ?>
          <?php $sleeps = (int) ($property['sleeps'] ?? 0); ?>
          <?php if ($bedrooms > 0 || $bathrooms > 0 || $sleeps > 0): ?>
            <ul class="property-amenity-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:var(--space-2);margin:var(--space-4) 0;">
              <?php if ($bedrooms > 0): ?>
                <li style="display:flex;align-items:center;gap:var(--space-1);"><i class="fa-solid fa-bed"></i> <?= $bedrooms ?> bedroom<?= $bedrooms !== 1 ? 's' : '' ?></li>
              <?php endif; ?>
              <?php if ($bathrooms > 0): ?>
                <li style="display:flex;align-items:center;gap:var(--space-1);"><i class="fa-solid fa-bath"></i> <?= $bathrooms ?> bathroom<?= $bathrooms !== 1 ? 's' : '' ?></li>
              <?php endif; ?>
              <?php if ($sleeps > 0): ?>
                <li style="display:flex;align-items:center;gap:var(--space-1);"><i class="fa-solid fa-user-group"></i> Sleeps <?= $sleeps ?></li>
              <?php endif; ?>
            </ul>
          <?php endif; ?>

          <?php if (!empty($property['amenities'])): ?>
            <h3 style="margin-top:var(--space-4);">What this place offers</h3>
            <ul class="amenities-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:var(--space-2);">
              <?php foreach ($property['amenities'] as $amenity): ?>
                <li style="display:flex;align-items:center;gap:var(--space-1);padding:var(--space-1) 0;">
                  <i class="fa-solid fa-check" style="color:var(--success);"></i>
                  <?= e($amenity) ?>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </section>

        <?php if (!empty($property['reviews'])): ?>
          <section id="reviews" class="property-reviews">
            <h2>Guest reviews</h2>
            <?php foreach ($property['reviews'] as $review): ?>
              <div class="review-card" style="border-radius:8px;padding:var(--space-3);margin-bottom:var(--space-2);border:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;">
                  <strong><?= e($review['guest_name'] ?? 'Anonymous') ?></strong>
                  <?php if (!empty($review['rating'])): ?>
                    <span style="color:var(--rating);">
                      <?php for ($i = 0; $i < (int) $review['rating']; $i++): ?>
                        <i class="fa-solid fa-star" style="font-size:var(--text-xs);"></i>
                      <?php endfor; ?>
                    </span>
                  <?php endif; ?>
                </div>
                <p style="margin:var(--space-1) 0;"><?= e($review['comment'] ?? '') ?></p>
                <?php if (!empty($review['date'])): ?>
                  <small class="text-muted"><?= e($review['date']) ?></small>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>

        <section id="location" class="property-location-section">
          <h2>Location</h2>
          <p class="text-muted"><?= e($property['address'] ?? ($property['city'] ?? 'Dar es Salaam, Tanzania')) ?></p>
          <?php if (!empty($property['lat']) && !empty($property['lng'])): ?>
            <div class="map-placeholder" style="height:240px;border-radius:8px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;margin-top:var(--space-2);">
              <span class="text-muted">Map: <?= e($property['lat']) ?>, <?= e($property['lng']) ?></span>
            </div>
          <?php endif; ?>
        </section>
      </main>

      <!-- Booking sidebar -->
      <aside class="property-sidebar">
        <form method="POST" action="/checkout" class="booking-card" data-validate>
          <input type="hidden" name="_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="property_id" value="<?= e($property['id']) ?>">

          <?php if (!empty($property['instant_book'])): ?>
            <span class="badge badge--success" style="margin-bottom:var(--space-2);">
              <i class="fa-solid fa-bolt" aria-hidden="true"></i> Instant book
            </span>
          <?php endif; ?>

          <div class="form-group">
            <label for="check_in">Check in</label>
            <input id="check_in" name="check_in" type="date" required>
          </div>
          <div class="form-group">
            <label for="check_out">Check out</label>
            <input id="check_out" name="check_out" type="date" required>
          </div>
          <div class="form-group">
            <label for="room_type_id">Room type</label>
            <select id="room_type_id" name="room_type_id" required>
              <?php if (!empty($property['room_types'])): ?>
                <?php foreach ($property['room_types'] as $rt): ?>
                  <option value="<?= e($rt['id']) ?>">
                    <?= e($rt['name']) ?> — Tshs <?= e(number_format((float) ($rt['price'] ?? 0), 0, '.', ',')) ?>
                    <?= e($rt['max_occupancy']) ?> guests
                  </option>
                <?php endforeach; ?>
              <?php else: ?>
                <option value="">Select a room type</option>
              <?php endif; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="guests">Guests</label>
            <input id="guests" name="guests" type="number" min="1" value="1" required>
          </div>

          <div style="border-top:2px solid var(--border);margin:var(--space-3) 0;padding-top:var(--space-3);">
            <div style="display:flex;justify-content:space-between;">
              <span class="text-muted">Tshs <?= e(number_format((float) ($property['price_per_night'] ?? 0), 0, '.', ',')) ?> × <?= $nights ?: 1 ?> nights</span>
              <span id="totalAmount">> Tshs <?= e(number_format((float) ($property['price_per_night'] ?? 0) * ($nights ?: 1), 0, '.', ',')) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:var(--space-1);">
              <span class="text-muted">Taxes & fees</span>
              <span id="taxAmount">Tshs <?= e(number_format((float) (($property['price_per_night'] ?? 0) * ($nights ?: 1)) * 0.15, 0, '.', ',')) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:var(--text-lg);margin-top:var(--space-2);">
              <span>Total</span>
              <span id="grandTotal">Tshs <?= e(number_format((float) (($property['price_per_night'] ?? 0) * ($nights ?: 1)) * 1.15, 0, '.', ',')) ?></span>
            </div>
          </div>

          <button type="submit" class="btn btn--primary btn--block" style="margin-top:var(--space-3);">
            <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> Check availability & book
          </button>
        </form>
      </aside>
    </div>
  </div>
</article>
<?php View::stop('content') ?>
