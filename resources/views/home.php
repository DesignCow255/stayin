<?php
/** @var array $featured @var array $recent @var array $topRated @var array $regions @var array $typeCounts @var int $publishedCount */
use App\Core\View;

View::start('content');
?>

<!-- 02 · Hero slideshow + search -->
<section class="si-hero si-hero--carousel"<?= track('home_view', ['surface' => 'home']) ?>>
  <div class="si-hero__media">
    <?php $locale = app_locale(); ?>
    <?php if (!empty($slides)): ?>
      <?php foreach ($slides as $i => $slide): ?>
        <?php $title = (string) ($locale === 'sw' ? ($slide['title_sw'] ?? '') : ($slide['title_en'] ?? '')); ?>
        <?php $desc = (string) ($locale === 'sw' ? ($slide['description_sw'] ?? '') : ($slide['description_en'] ?? '')); ?>
        <?php $cta = (string) ($locale === 'sw' ? ($slide['cta_text_sw'] ?? '') : ($slide['cta_text_en'] ?? '')); ?>
        <div class="si-hero__slide" aria-hidden="<?= $i > 0 ? 'true' : 'false' ?>">
          <img src="<?= e(image_url($slide['image_url'] ?? null, stayin_image_asset($title ?: 'hero'))) ?>" alt="<?= e($title ?: 'StayIn offer') ?>" width="1920" height="1080" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" decoding="async" fetchpriority="<?= $i === 0 ? 'high' : 'low' ?>">
        </div>
      <?php endforeach; ?>
    <?php elseif (!empty($heroImage)): ?>
      <img src="<?= e(image_url($heroImage, stayin_image_asset((string) ($heroProperty['region'] ?? 'hero')))) ?>" alt="<?= e($heroProperty['name'] ?? 'A stay in Tanzania') ?>" width="1920" height="1080" fetchpriority="high" decoding="async">
    <?php else: ?>
      <div class="si-hero__slide"><img src="<?= e(asset(stayin_image_asset('hero'))) ?>" alt="" width="1920" height="1080" decoding="async"></div>
    <?php endif; ?>
    <div class="si-hero__scrim"></div>
  </div>
  <div class="si-hero__inner-wrap">
    <div class="si-container si-hero__inner">
      <p class="si-eyebrow si-hero__eyebrow"><?= e(number_format($publishedCount)) ?> verified stays across Tanzania</p>
      <h1 class="si-hero__title">Exceptional places, effortlessly discovered.</h1>
      <p class="si-hero__sub">Real availability, transparent pricing and verified hosts — from Kilimanjaro foothills to the Indian Ocean coast.</p>
      <?php
      // CTA from the first active slide (if any), otherwise the default browse action.
      $slideCtaLink = !empty($slides[0]['cta_link']) ? (string) $slides[0]['cta_link'] : '/search';
      $slideCtaLabel = $cta !== '' ? $cta : (app_locale() === 'sw' ? 'Fanya utafutishaji' : 'Find your stay');
      ?>
      <a href="<?= e(url($slideCtaLink)) ?>" class="si-btn si-btn--primary si-hero__cta"<?= track('search_performed', ['surface' => 'hero_cta']) ?>><?= $slideCtaLabel === '' ? e(app_locale() === 'sw' ? 'Fanya utafutishaji' : 'Find your stay') : e($slideCtaLabel) ?><?= icon('arrow-right', 'si-icon--sm') ?></a>
      <?php if (!empty($slides)): ?>
        <div class="si-hero__nav">
          <button type="button" class="si-icon-btn si-hero__nav-btn" id="heroPrev" aria-label="Previous slide"><?= icon('chevron-left', 'si-icon--lg') ?></button>
          <button type="button" class="si-icon-btn si-hero__nav-btn" id="heroNext" aria-label="Next slide"><?= icon('chevron-right', 'si-icon--lg') ?></button>
        </div>
      <?php endif; ?>
    </div>
    <div class="si-container si-search">
      <form class="si-search__card" action="<?= e(url('/search')) ?>" method="get" role="search" aria-label="Find a stay"<?= track('search_performed', ['surface' => 'hero']) ?>>
        <div class="si-field">
          <label for="hero-destination">Where to</label>
          <div class="si-input-group">
            <?= icon('map-pin', 'si-icon--sm') ?>
            <input class="si-input" id="hero-destination" name="q" type="search" placeholder="Region, town or property name" autocomplete="off" list="hero-destinations">
        </div>
        <datalist id="hero-destinations">
          <?php foreach ($regions as $region): ?>
          <option value="<?= e($region['region']) ?>"><?= e($region['count']) ?> stays</option>
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="si-field">
        <label for="hero-in">Check in</label>
        <input class="si-input" id="hero-in" name="check_in" type="date">
      </div>
      <div class="si-field">
        <label for="hero-out">Check out</label>
        <input class="si-input" id="hero-out" name="check_out" type="date">
      </div>
      <div class="si-field">
        <label for="hero-guests">Guests</label>
        <input class="si-input" id="hero-guests" name="guests" type="number" value="2" min="1" max="50" inputmode="numeric">
      </div>
      <button class="si-btn si-btn--primary si-search__submit" type="submit">Search stays<?= icon('arrow-right', 'si-icon--sm') ?></button>
    </form>
    <div class="si-search__meta">
      <ul class="si-recent">
        <li>Popular:</li>
        <?php foreach (array_slice($regions, 0, 4) as $region): ?>
        <li><a class="si-chip si-chip--static" href="<?= e(region_url($region['region'])) ?>"><?= e($region['region']) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <a class="si-link" href="<?= e(url('/search')) ?>">All search filters<?= icon('sliders', 'si-icon--sm') ?></a>
    </div>
    </div>
  </div>
</section>


<!-- 04 · Explore by location -->
<?php if ($regions): ?>
<section class="si-section" id="destinations" style="padding-block-start:0">
  <div class="si-container">
    <div class="si-section-head">
      <div class="si-section-head__text">
        <p class="si-eyebrow">Explore by location</p>
        <h2 class="si-title si-title--sm">Find your surroundings</h2>
        <p class="si-section-head__intro">A change of pace, in a place of your own.</p>
      </div>
    </div>
    <div class="si-grid-destinations">
      <?php foreach ($regions as $i => $region): ?>
      <a class="si-destination si-reveal" href="<?= e(region_url($region['region'])) ?>" data-reveal-delay="<?= e((string) ($i % 4 + 1)) ?>">
        <img src="<?= e(image_url($region['cover'] ?? null, stayin_image_asset((string) ($region['region'] ?? 'Tanzania')))) ?>" alt="" loading="lazy" decoding="async" width="800" height="600">
        <p class="si-eyebrow" style="color:#E9D8AC;margin:0">Tanzania</p>
        <span class="si-destination__region"><?= e($region['region']) ?></span>
        <span class="si-destination__meta"><?= e(number_format((int) $region['count'])) ?> <?= (int) $region['count'] === 1 ? 'stay' : 'stays' ?><?= icon('arrow-right', 'si-icon--sm') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 05 · Property categories -->
<?php if ($typeCounts): ?>
<section class="si-section" style="padding-block-start:0">
  <div class="si-container">
    <div class="si-section-head">
      <div class="si-section-head__text">
        <p class="si-eyebrow">Browse by type</p>
        <h2 class="si-title si-title--sm">Whatever kind of stay you have in mind</h2>
      </div>
    </div>
    <div class="si-grid-types">
      <?php foreach ($typeCounts as $type): ?>
      <?php $typeValue = (string) $type['property_type']; ?>
      <a class="si-type si-reveal" href="<?= e(url('/search?' . http_build_query(['property_type' => $typeValue]))) ?>">
        <?= icon(\App\Support\PropertyType::icon($typeValue), 'si-icon--lg') ?>
        <span class="si-type__label"><?= e(property_type_plural($typeValue)) ?></span>
        <span class="si-type__count"><?= e(number_format((int) $type['count'])) ?> available</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="si-section">
  <div class="si-container">
    <div class="si-section-head">
      <div class="si-section-head__text">
        <p class="si-eyebrow">Featured</p>
        <h2 class="si-title si-title--sm">Places with character</h2>
      </div>
      <a class="si-link" href="<?= e(url('/stays')) ?>">View all stays<?= icon('arrow-right', 'si-icon--sm') ?></a>
    </div>
    <?php if ($featured): ?>
    <div class="si-grid-cards">
      <?php foreach (array_slice($featured, 0, 6) as $property): ?>
      <?= View::component('property-card', ['property' => $property]) ?>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="si-empty si-surface">
      <?= icon('hotel') ?>
      <h3>A collection in the making</h3>
      <p>No featured stays yet. Explore everything our hosts have published so far.</p>
      <a href="<?= e(url('/stays')) ?>" class="si-btn si-btn--outline">Browse stays</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- 07 · Trust -->
<section class="si-section">
  <div class="si-container">
    <div class="si-grid-cards" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
      <div class="si-surface si-surface--pad si-reveal">
        <?= icon('badge-check', 'si-icon--lg') ?>
        <h3 class="si-title si-title--sm" style="font-size:var(--si-text-lg);margin-block-start:var(--si-space-3)">Verified hosts</h3>
        <p class="si-caption" style="margin:0">Every host is identity-checked before a listing goes live, and each property carries its verification status.</p>
      </div>
      <div class="si-surface si-surface--pad si-reveal" data-reveal-delay="1">
        <?= icon('calendar', 'si-icon--lg') ?>
        <h3 class="si-title si-title--sm" style="font-size:var(--si-text-lg);margin-block-start:var(--si-space-3)">Real availability</h3>
        <p class="si-caption" style="margin:0">The dates you search are the dates the host holds — availability is checked again before you pay.</p>
      </div>
      <div class="si-surface si-surface--pad si-reveal" data-reveal-delay="2">
        <?= icon('receipt', 'si-icon--lg') ?>
        <h3 class="si-title si-title--sm" style="font-size:var(--si-text-lg);margin-block-start:var(--si-space-3)">Transparent pricing</h3>
        <p class="si-caption" style="margin:0">Nights, fees and taxes shown in full before you confirm. Your price is held for 15 minutes.</p>
      </div>
      <div class="si-surface si-surface--pad si-reveal" data-reveal-delay="3">
        <?= icon('shield-check', 'si-icon--lg') ?>
        <h3 class="si-title si-title--sm" style="font-size:var(--si-text-lg);margin-block-start:var(--si-space-3)">Secure payment</h3>
        <p class="si-caption" style="margin:0">Pay the way you already pay, with a receipt for every confirmed booking.</p>
      </div>
    </div>
  </div>
</section>

<!-- 08 · Recently added -->
<?php if ($recent): ?>
<section class="si-section" style="padding-block-start:0">
  <div class="si-container">
    <div class="si-section-head">
      <div class="si-section-head__text">
        <p class="si-eyebrow">Recently added</p>
        <h2 class="si-title si-title--sm">Fresh on StayIn</h2>
      </div>
      <a class="si-link" href="<?= e(url('/search?sort=newest')) ?>">See what's new<?= icon('arrow-right', 'si-icon--sm') ?></a>
    </div>
    <div class="si-grid-cards">
      <?php foreach (array_slice($recent, 0, 6) as $property): ?>
      <?= View::component('property-card', ['property' => $property]) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- 10 · Host CTA -->
<section class="si-section" style="padding-block-start:0">
  <div class="si-container">
    <div class="si-surface si-surface--pad-lg si-reveal" style="background:var(--si-brand);border-color:var(--si-brand);color:var(--si-brand-contrast)">
      <p class="si-eyebrow" style="color:var(--si-premium)">For hosts</p>
      <h2 class="si-title si-title--sm" style="margin-block:var(--si-space-3) var(--si-space-3)">List your property where travellers are already looking.</h2>
      <p class="si-lede" style="color:rgba(255,255,255,.86);margin-block-end:var(--si-space-5)">Publish a listing, keep your calendar accurate and receive enquiries — StayIn handles verification, payments and receipts.</p>
      <div class="si-row">
        <a href="<?= e(url('/register')) ?>" class="si-btn si-btn--inverse">Create a host account<?= icon('arrow-right', 'si-icon--sm') ?></a>
        <a href="<?= e(url('/help')) ?>" class="si-link" style="color:rgba(255,255,255,.9)">How hosting works</a>
      </div>
    </div>
  </div>
</section>

<!-- 14 · Newsletter -->
<section class="si-section" style="padding-block-start:0">
  <div class="si-container">
    <div class="si-surface si-surface--pad-lg si-reveal" style="max-width:820px">
      <p class="si-eyebrow si-eyebrow--muted">Stay in the loop</p>
      <h2 class="si-title si-title--sm" style="margin-block:var(--si-space-3) var(--si-space-2)">Occasional emails about new stays and regions.</h2>
      <p class="si-caption">One confirmation email, then only what you asked for. Unsubscribe any time.</p>
      <form method="POST" action="<?= e(url('/newsletter')) ?>" class="si-row" style="margin-block-start:var(--si-space-5)"<?= track('newsletter_subscribed', ['surface' => 'home']) ?>>
        <?= csrf_field() ?>
        <div class="si-field" style="flex:1 1 260px">
          <label for="newsletter-email" class="sr-only">Email address</label>
          <input class="si-input" id="newsletter-email" name="email" type="email" required autocomplete="email" placeholder="you@example.com">
        </div>
        <label class="si-check" style="min-height:0">
          <input type="checkbox" name="consent" value="1" required>
          <span>Yes, send me StayIn travel emails</span>
        </label>
        <button class="si-btn si-btn--primary" type="submit">Create alert<?= icon('arrow-right', 'si-icon--sm') ?></button>
      </form>
    </div>
  </div>
</section>

<?php View::stop(); ?>

