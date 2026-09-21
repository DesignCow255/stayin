<?php
/**
 * Discovery + search results (§8, §9).
 *
 * @var array $properties @var int $count @var int $page @var int $total_pages @var array $filters
 */
use App\Core\View;

View::start('content');

$activeFilters = array_filter($filters, static fn ($v) => $v !== null && $v !== '');
$filterCount = active_filter_count($filters);
$searchUrl = url('/search');

/** Remove one filter from the current query set, preserving everything else. */
$without = static function (string $key) use ($activeFilters, $searchUrl): string {
    $params = $activeFilters;
    unset($params[$key]);
    return $searchUrl . (count($params) ? '?' . http_build_query($params) : '');
};
?>

<div class="si-container">
  <header class="si-section-head" style="margin-block-end:var(--si-space-5)">
    <div class="si-section-head__text">
      <p class="si-eyebrow">Find your surroundings</p>
      <h1 class="si-title">A stay that suits you.</h1>
      <p class="si-section-head__intro">Choose a destination and dates. Every result is a real, published listing.</p>
    </div>
  </header>

  <form class="si-search__card" action="<?= e($searchUrl) ?>" method="get" role="search" aria-label="Refine your search"<?= track('search_filter_applied', ['surface' => 'search_form']) ?>>
    <div class="si-field">
      <label for="q">Destination or property</label>
      <div class="si-input-group">
        <?= icon('search', 'si-icon--sm') ?>
        <input class="si-input" id="q" name="q" type="search" placeholder="Region, town or property name" value="<?= e($filters['q'] ?? '') ?>">
      </div>
    </div>
    <div class="si-field">
      <label for="check_in">Check in</label>
      <input class="si-input" id="check_in" name="check_in" type="date" value="<?= e($filters['check_in'] ?? '') ?>">
    </div>
    <div class="si-field">
      <label for="check_out">Check out</label>
      <input class="si-input" id="check_out" name="check_out" type="date" value="<?= e($filters['check_out'] ?? '') ?>">
    </div>
    <div class="si-field">
      <label for="guests">Guests</label>
      <input class="si-input" id="guests" name="guests" type="number" min="1" max="50" value="<?= e($filters['guests'] ?? 1) ?>">
    </div>
    <div class="si-field">
      <label for="property_type">Property type</label>
      <select class="si-select" id="property_type" name="property_type">
        <option value="">All types</option>
        <?php foreach (\App\Support\PropertyType::options() as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= ($filters['property_type'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="si-field">
      <label for="rating">Guest rating</label>
      <select class="si-select" id="rating" name="rating">
        <option value="">Any rating</option>
        <?php foreach (['4' => '4.0 and above', '4.5' => '4.5 and above', '4.8' => '4.8 and above'] as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= ($filters['rating'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="si-field">
      <label for="max_price">Max price / night</label>
      <input class="si-input" id="max_price" name="max_price" type="number" min="0" step="any" placeholder="Any" value="<?= e($filters['max_price'] ?? '') ?>" inputmode="numeric">
    </div>
    <div class="si-field">
      <label for="sort">Sort by</label>
      <select class="si-select" id="sort" name="sort">
        <?php foreach (['' => 'Recommended', 'rating' => 'Guest rating', 'newest' => 'Recently added', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'] as $value => $label): ?>
        <option value="<?= e($value) ?>"<?= ($filters['sort'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="si-field">
      <label class="si-check" for="verified" style="min-height:48px;padding-block:0">
        <input type="checkbox" id="verified" name="verified" value="1"<?= !empty($filters['verified']) ? ' checked' : '' ?>>
        <span>Verified only</span>
      </label>
    </div>
    <button class="si-btn si-btn--primary si-search__submit" type="submit">Update results<?= icon('arrow-right', 'si-icon--sm') ?></button>
  </form>

  <div class="si-toolbar">
    <p class="si-toolbar__count"><strong><?= e(number_format($count)) ?></strong> <?= $count === 1 ? 'stay' : 'stays' ?><?= !empty($filters['region']) ? ' in ' . e($filters['region']) : ' to explore' ?></p>
    <div class="si-row si-row--nowrap">
      <?php if ($filterCount > 0): ?>
      <a class="si-clear" href="<?= e($searchUrl) ?>">Clear all filters</a>
      <?php endif; ?>
      <a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/stays')) ?>"><?= icon('grid', 'si-icon--sm') ?> Browse all</a>
    </div>
  </div>

  <?php if ($filterCount > 0): ?>
  <div class="si-active-filters">
    <p class="si-active-filters__label" style="margin:0">Active:</p>
    <?php foreach ($activeFilters as $key => $value): ?>
    <?php
    $labels = [
        'q' => '“' . $value . '”',
        'region' => 'Region: ' . $value,
        'property_type' => property_type_label((string) $value),
        'rating' => $value . '+ rating',
        'verified' => 'Verified only',
        'min_price' => 'From ' . $value,
        'max_price' => 'Up to ' . $value,
        'currency' => 'Prices in ' . $value,
        'guests' => $value . ' guests',
        'rooms' => $value . ' rooms',
        'check_in' => 'In ' . format_date((string) $value, 'j M'),
        'check_out' => 'Out ' . format_date((string) $value, 'j M'),
    ];
    $label = $labels[$key] ?? (ucfirst($key) . ': ' . $value);
    ?>
    <a class="si-chip si-chip--static" href="<?= e($without((string) $key)) ?>"<?= track('search_filter_removed', ['surface' => 'chip', 'filter' => $key]) ?>>
      <span><?= e($label) ?></span>
      <span class="si-chip__remove"><?= icon('close', 'si-icon--sm') ?><span class="sr-only">Remove filter</span></span>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="si-grid-cards" style="margin-block-start:var(--si-space-6)">
    <?php if ($properties): ?>
    <?php foreach ($properties as $property): ?>
    <?= View::component('property-card', ['property' => $property]) ?>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!$properties): ?>
  <div class="si-empty si-surface" style="margin-block:var(--si-space-6)">
    <?= icon('search') ?>
    <h2>No stays match this search</h2>
    <p>Nothing matched those filters. Try relaxing one of them — here are the quickest ways to widen your search.</p>
    <div class="si-row" style="justify-content:center;margin-block-start:var(--si-space-3)">
      <?php if (!empty($filters['property_type'])): ?>
      <a class="si-chip" href="<?= e($without('property_type')) ?>">Remove “<?= e(property_type_label((string) $filters['property_type'])) ?>”</a>
      <?php endif; ?>
      <?php if (!empty($filters['max_price'])): ?>
      <a class="si-chip" href="<?= e($without('max_price')) ?>">Remove price limit</a>
      <?php endif; ?>
      <?php if (!empty($filters['check_in']) || !empty($filters['check_out'])): ?>
      <a class="si-chip" href="<?= e($without('check_in')) ?>">Any dates</a>
      <?php endif; ?>
      <?php if (!empty($filters['verified'])): ?>
      <a class="si-chip" href="<?= e($without('verified')) ?>">All properties</a>
      <?php endif; ?>
      <a class="si-chip" href="<?= e($searchUrl) ?>">Start over</a>
    </div>
  </div>
  <?php endif; ?>

  <?= View::component('pagination', ['page' => $page, 'total' => $count, 'perPage' => 24, 'baseUrl' => '/search', 'query' => $activeFilters]) ?>
</div>
<?php View::stop(); ?>

