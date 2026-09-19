<?php
/** @var array $hostData */
/** @var array|null $authUser */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container">
    <div class="host-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h1>Host dashboard</h1>
      <a href="/host/properties/new" class="btn btn--primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add property</a>
    </div>

    <?php if (!empty($hostData['stats'])): ?>
      <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-3);margin-bottom:var(--space-5);">
        <div class="stat-card card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;color:var(--primary);"><?= e($hostData['stats']['properties'] ?? 0) ?></div>
          <div class="text-muted" style="font-size:var(--text-sm);">Listings</div>
        </div>
        <div class="stat-card card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;color:var(--success);"><?= e($hostData['stats']['bookings'] ?? 0) ?></div>
          <div class="text-muted" style="font-size:var(--text-sm);">Bookings</div>
        </div>
        <div class="stat-card card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;color:var(--warning);">$<?= e(number_format((float) ($hostData['stats']['earnings'] ?? 0), 2)) ?></div>
          <div class="text-muted" style="font-size:var(--text-sm);">Est. earnings</div>
        </div>
        <div class="stat-card card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;color:var(--rating);"><?= e($hostData['stats']['rating'] ?? '—') ?></div>
          <div class="text-muted" style="font-size:var(--text-sm);">Rating</div>
        </div>
      </div>
    <?php endif; ?>

    <nav class="host-nav" style="display:flex;gap:var(--space-2);border-bottom:1px solid var(--border);margin-bottom:var(--space-4);overflow-x:auto;">
      <a href="/host" class="host-nav__link host-nav__link--active">Dashboard</a>
      <a href="/host/properties" class="host-nav__link">Properties</a>
      <a href="/host/analytics" class="host-nav__link">Analytics</a>
      <a href="/host/bookings" class="host-nav__link">Bookings</a>
      <a href="/host/reviews" class="host-nav__link">Reviews</a>
      <a href="/host/settings" class="host-nav__link">Settings</a>
    </nav>

    <?php if (!empty($hostData['recent_bookings'])): ?>
      <div style="margin-top:var(--space-4);">
        <h2>Recent bookings</h2>
        <div class="table-wrap">
          <table class="table">
            <thead><tr><th>Reference</th><th>Guest</th><th>Dates</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($hostData['recent_bookings'] as $b): ?>
                <tr>
                  <td><?= e($b['reference'] ?? '') ?></td>
                  <td><?= e($b['guest_name'] ?? '') ?></td>
                  <td><?= e($b['check_in'] ?? '') ?> – <?= e($b['check_out'] ?? '') ?></td>
                  <td><span class="badge badge--sm"><?= e($b['status'] ?? '') ?></span></td>
                  <td>$<?= e(number_format((float) ($b['total'] ?? 0), 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop() ?>
