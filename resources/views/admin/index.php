<?php
/** @var array $stats */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container">
    <h1>Admin dashboard</h1>
    <p class="text-muted">Overview of the StayIn platform.</p>
    <?php if (!empty($stats)): ?>
      <div class="admin-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:var(--space-3);margin:var(--space-4) 0;">
        <div class="card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;"><?= e($stats['users'] ?? 0) ?></div>
          <div class="text-muted">Users</div>
        </div>
        <div class="card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;"><?= e($stats['properties'] ?? 0) ?></div>
          <div class="text-muted">Properties</div>
        </div>
        <div class="card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;"><?= e($stats['bookings'] ?? 0) ?></div>
          <div class="text-muted">Bookings</div>
        </div>
        <div class="card" style="padding:var(--space-3);text-align:center;">
          <div style="font-size:var(--text-2xl);font-weight:700;">$<?= e(number_format((float) ($stats['payments_total'] ?? 0), 2)) ?></div>
          <div class="text-muted">Payments</div>
        </div>
      </div>
    <?php endif; ?>
    <nav style="display:flex;gap:var(--space-2);flex-wrap:wrap;">
      <a href="/admin/properties" class="btn btn--ghost">Properties</a>
      <a href="/admin/users" class="btn btn--ghost">Users</a>
      <a href="/admin/bookings" class="btn btn--ghost">Bookings</a>
      <a href="/admin/payments" class="btn btn--ghost">Payments</a>
      <a href="/admin/reviews" class="btn btn--ghost">Reviews</a>
    </nav>
  </div>
</section>
<?php View::stop() ?>
