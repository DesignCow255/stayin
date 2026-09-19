<?php
/** @var App\Models\Property[] $properties */
/** @var array<string,mixed> $filters */
/** @var string|null $currency */
/** @var int $nights */
use App\Core\View;
?>
<?php View::start('content') ?>
<section class="page-section">
  <div class="container">
    <div class="search-layout">
      <aside class="search-sidebar" role="complementary" aria-label="Filters">
        <form method="GET" action="/search" class="search-form" data-validate>
          <div class="search-card">
            <div class="search-card__row">
              <div class="search-card__field"><label for="location">Location</label><input id="location" name="location" type="text" placeholder="Region or city" value="<?= e($filters['location'] ?? '') ?>" aria-label="Location"></div>
              <div class="search-card__field"><label for="check_in">Check in</label><input id="check_in" name="check_in" type="date" value="<?= e($filters['check_in'] ?? '') ?>" aria-label="Check in"></div>
              <div class="search-card__field"><label for="check_out">Check out</label><input id="check_out" name="check_out" type="date" value="<?= e($filters['check_out'] ?? '') ?>" aria-label="Check out"></div>
              <div class="search-card__field"><label for="guests">Guests</label><input id="guests" name="guests" type="number" min="1" max="50" value="<?= e($filters['guests'] ?? 1) ?>" aria-label="Number of guests"></div>
            </div>
          </div>
          <h3 style="font-size:var(--text-sm);font-weight:700;margin:var(--space-4) 0 var(--space-2);">Price (Tshs)</h3>
          <div class="search-card__row search-card__row--filter">
            <div class="search-card__field"><label for="min_price">Min price</label><input id="min_price" name="min_price" type="number" min="0" step="1000" placeholder="Min" value="<?= e($filters['min_price'] ?? '') ?>" aria-label="Minimum price"></div>
            <div class="search-card__field"><label for="max_price">Max price</label><input id="max_price" name="max_price" type="number" min="0" step="1000" placeholder="Max" value="<?= e($filters['max_price'] ?? '') ?>" aria-label="Maximum price"></div>
          </div>
          <h3 style="font-size:var(--text-sm);font-weight:700;margin:var(--space-4) 0 var(--space-2);">Rating</h3>
          <div class="search-card__row">
            <div class="search-card__field"><select id="rating" name="rating" aria-label="Minimum rating"><option value="">Any rating</option><option value="4"<?= ($filters['rating'] ?? '') === '4' ? ' selected' : '' ?>>4+ stars</option><option value="4.5"<?= ($filters['rating'] ?? '') === '4.5' ? ' selected' : '' ?>>4.5+ stars</option><option value="4.8"<?= ($filters['rating'] ?? '') === '4.8' ? ' selected' : '' ?>>4.8+ stars</option></select></div>
          </div>
          <h3 style="font-size:var(--text-sm);font-weight:700;margin:var(--space-4) 0 var(--space-2);">Property type</h3>
          <div class="search-card__row">
            <div class="search-card__field search-card__field--checkbox"><label class="checkbox-label"><input type="checkbox" name="verified" value="1"<?= !empty($filters['verified']) ? ' checked' : '' ?>> Verified hosts only</label></div>
          </div>
          <h3 style="font-size:var(--text-sm);font-weight:700;margin:var(--space-4) 0 var(--space-2);">Sort by</h3>
          <div class="search-card__row search-card__row--sort"><label style="font-size:var(--text-xs);color:var(--text-muted);font-weight:600">Sort</label><select id="sort" name="sort" aria-label="Sort by"><option value="">Recommended</option><option value="rating"<?= ($filters['sort'] ?? '') === 'rating' ? ' selected' : '' ?>>Rating</option><option value="newest"<?= ($filters['sort'] ?? '') === 'newest' ? ' selected' : '' ?>>Newest</option><option value="price_low"<?= ($filters['sort'] ?? '') === 'price_low' ? ' selected' : '' ?>>Price: low to high</option><option value="price_high"<?= ($filters['sort'] ?? '') === 'price_high' ? ' selected' : '' ?>>Price: high to low</option></select></div>
          <button type="submit" class="btn btn--primary btn--block" style="margin-top:var(--space-4);"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search</button>
        </form>
      </aside>
      <main class="search-results" role="main">
        <?php if (empty($properties)): ?>
          <div class="empty-state" style="text-align:center;padding:var(--space-8) 0;">
            <i class="fa-solid fa-magnifying-glass-question" style="font-size:3rem;color:var(--text-faint);margin-bottom:var(--space-4);" aria-hidden="true"></i>
            <h3>No stays match your search</h3>
            <p class="text-muted">Try adjusting your dates or filters above.</p>
          </div>
        <?php else: ?>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-var(--space-4)">
            <?php foreach ($properties as $property): ?>
              <?= View::component('property-card', ['property' => $property, 'currency' => $currency, 'nights' => $nights]) ?>
            <?php endforeach; ?>
          </div>
          <?php if (($filters['total_pages'] ?? 1) > 1): ?>
            <nav class="pagination" role="navigation" aria-label="Search results pages">
              <?php $current = (int) ($filters['page'] ?? 1); ?>
              <?php for ($i = 1; $i <= $filters['total_pages']; $i++): ?>
                <a href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"
                  class="pagination__link<?= $i === $current ? ' pagination__link--active' : '' ?>"
                  <?= $i === $current ? 'aria-current="page"' : '' ?>><?= $i ?></a>
              <?php endfor; ?>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </main>
    </div>
  </div>
</section>
<?php View::stop('content') ?>
