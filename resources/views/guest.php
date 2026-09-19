<?php
/** @var array|null $authUser */
/** @var array $upcomingBookings */
/** @var array $pastBookings */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container">
    <div class="guest-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-5);">
      <h1>My StayIn</h1>
      <a href="/preferences" class="btn btn--ghost"><i class="fa-solid fa-cog" aria-hidden="true"></i> Preferences</a>
    </div>

    <nav style="display:flex;gap:var(--space-2);border-bottom:1px solid var(--border);margin-bottom:var(--space-4);overflow-x:auto;">
      <a href="/guest" class="guest-nav__link guest-nav__link--active">My trips</a>
      <a href="/guest/wishlist" class="guest-nav__link">Wishlist</a>
      <a href="/guest/reviews" class="guest-nav__link">My reviews</a>
    </nav>

    <h2>Upcoming trips</h2>
    <?php if (empty($upcomingBookings)): ?>
      <div class="empty-state" style="text-align:center;padding:var(--space-6) 0;">
        <i class="fa-solid fa-calendar-xmark" style="font-size:2rem;color:var(--text-faint);" aria-hidden="true"></i>
        <p class="text-muted">No upcoming trips. <a href="/search">Search stays</a> to get started.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap:var(--space-3)">
        <?php foreach ($upcomingBookings as $booking): ?>
          <div class="card" style="padding:var(--space-3);">
            <h3><?= e($booking['property_name'] ?? 'Property') ?></h3>
            <p class="text-muted">
              <i class="fa-solid fa-calendar" aria-hidden="true"></i>
              <?= e($booking['check_in'] ?? '') ?> – <?= e($booking['check_out'] ?? '') ?>
            </p>
            <div style="display:flex;justify-content:space-between;">
              <span class="text-muted">Total</span>
              <strong>$<?= e(number_format((float) ($booking['total'] ?? 0), 2)) ?></strong>
            </div>
            <span class="badge badge--sm badge--pending">
              <?= e(ucfirst($booking['status'] ?? 'confirmed')) ?>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 style="margin-top:var(--space-6);">Past trips</h2>
    <?php if (empty($pastBookings)): ?>
      <p class="text-muted">No past trips.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Property</th><th>Dates</th><th>Status</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($pastBookings as $booking): ?>
              <tr>
                <td><?= e($booking['property_name'] ?? '') ?></td>
                <td><?= e($booking['check_in'] ?? '') ?> – <?= e($booking['check_out'] ?? '') ?></td>
                <td><?= e($booking['status'] ?? '') ?></td>
                <td>$<?= e(number_format((float) ($booking['total'] ?? 0), 2)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php View::stop() ?>
