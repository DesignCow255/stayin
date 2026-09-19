<?php /** @var array $regions */ /** @var array $featured */ /** @var array $popular */ /** @var int $publishedCount */ ?>
<section class="hero">
  <div class="container">
    <div class="hero__inner">
      <div class="hero__content">
        <span class="badge badge--brand">Tanzania's stay marketplace</span>
        <h1 class="hero__title">Book distinctive stays<br>across Tanzania</h1>
        <p class="hero__sub">Verified hosts · transparent pricing in Tshs and USD · secure mobile money payments</p>
        <form class="search-bar" action="/search" method="get" role="search" aria-label="Search stays">
          <div class="search-bar__field">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input name="q" type="search" placeholder="Where or what are you looking for?" aria-label="Search" value="<?= e(old('q')) ?>">
            <button type="submit" class="btn btn--primary">Search</button>
          </div>
        </form>
        <div class="hero__stats">
          <div><strong class="hero__stat-value"><?= e(number_format($publishedCount)) ?></strong><span>published stays</span></div>
          <?php foreach (array_slice($regions, 0, 3) as $region): ?>
          <div><strong class="hero__stat-value"><?= e(number_format((int) ($region['count'] ?? 0))) ?></strong><span>in <?= e($region['region'] ?? '') ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="hero__media">
        <div class="hero__media-grid">
        <?php if (empty($featured)): ?>
          <div class="hero__empty-media"><i class="fa-solid fa-hotel" aria-hidden="true"></i><span>No featured stays yet</span></div>
        <?php else: ?>
          <?php foreach (array_slice($featured, 0, 3) as $property): ?>
          <a href="/property/<?= e($property['slug']) ?>" class="hero__media-card" aria-label="<?= e($property['name'] ?? '') ?>">
            <img src="<?= e(image_url($property['cover_image'] ?? null)) ?>" alt="<?= e($property['name'] ?? '') ?>" loading="lazy">
            <div class="hero__media-overlay">
              <span><?= e($property['region'] ?? '') ?></span>
              <strong><?= format_money((float) ($property['min_price'] ?? 0), $property['currency'] ?? 'TZS') ?></strong>
            </div>
          </a>
          <?php endforeach; ?>
        <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section destinations">
  <div class="container">
    <div class="section-head">
      <h2>Explore by region</h2>
      <a href="/stays" class="btn btn--ghost btn--sm">All stays</a>
    </div>
    <?php if (empty($regions)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
      <h3>No regions yet</h3>
      <p>Stays will appear here as hosts publish listings.</p>
    </div>
    <?php else: ?>
    <div class="grid grid--destinations">
      <?php foreach ($regions as $region): ?>
      <a href="/stays/<?= e(str_replace(' ', '-', mb_strtolower($region['region'] ?? ''))) ?>" class="destination-card">
        <span class="destination-card__count"><?= e(number_format((int) ($region['count'] ?? 0))) ?></span>
        <span class="destination-card__name"><?= e($region['region'] ?? '') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section featured">
  <div class="container">
    <div class="section-head">
      <h2>Featured stays</h2>
      <a href="/stays" class="btn btn--ghost btn--sm">View all</a>
    </div>
    <?php if (empty($featured)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-star" aria-hidden="true"></i>
      <h3>Coming soon</h3>
      <p>Featured stays will appear here.</p>
    </div>
    <?php else: ?>
    <div class="grid grid--cards">
      <?php foreach ($featured as $property): ?>
      <?= View::component('property-card', ['property' => $property]) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section popular">
  <div class="container">
    <div class="section-head">
      <h2>Popular stays</h2>
      <a href="/stays" class="btn btn--ghost btn--sm">Browse all</a>
    </div>
    <?php if (empty($popular)): ?>
    <div class="empty-state">
      <i class="fa-solid fa-hotel" aria-hidden="true"></i>
      <h3>No stays published yet</h3>
      <p>Discover stays will appear here as hosts publish listings.</p>
    </div>
    <?php else: ?>
    <div class="grid grid--cards">
      <?php foreach ($popular as $property): ?>
      <?= View::component('property-card', ['property' => $property]) ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>