<?php
/** @var array|null $authUser */ /** @var array $bookings */ /** @var array|null $nextTrip */ /** @var array $travelStats */ /** @var array $saved */ /** @var array $notifications */ /** @var array $payments */
use App\Core\View;
View::start('content');
$user = $authUser ?? ($user ?? []);
$firstName = (string)($user['first_name'] ?? 'Guest');
$upcoming = array_values(array_filter($bookings, static fn($b) => strtotime((string)($b['check_out'] ?? '1970-01-01')) >= strtotime(date('Y-m-d')) && in_array($b['status'] ?? '', ['confirmed','awaiting_payment','pending'], true)));
$past = array_values(array_filter($bookings, static fn($b) => strtotime((string)($b['check_out'] ?? '1970-01-01')) < strtotime(date('Y-m-d')) || ($b['status'] ?? '') === 'completed'));
$cancelled = array_values(array_filter($bookings, static fn($b) => ($b['status'] ?? '') === 'cancelled'));
$timeline = ['Booked','Payment confirmed','Upcoming','Check-in','Stay','Check-out','Review'];
$currentStage = 0;
if ($nextTrip) { $currentStage = ($nextTrip['payment_status'] ?? '') === 'paid' ? 2 : 1; if (strtotime($nextTrip['check_in']) <= time()) $currentStage = 4; }
?>
<section class="si-app-dashboard">
  <div class="si-dashboard-shell">
    <aside class="si-dashboard-sidebar" aria-label="Guest navigation">
      <div><p class="si-eyebrow">Travel concierge</p><strong><?= e($firstName) ?>'s StayIn</strong><p class="si-caption">Trips, saved places, receipts and notifications.</p></div>
      <nav class="si-dashboard-nav">
        <span class="si-dashboard-nav__label">Travel</span>
        <a href="<?= e(url('/guest')) ?>" aria-current="page"><?= icon('hotel') ?>Trips</a>
        <a href="<?= e(url('/search')) ?>"><?= icon('compass') ?>Explore</a>
        <a href="<?= e(url('/guest/favourites')) ?>"><?= icon('heart') ?>Saved</a>
        <span class="si-dashboard-nav__label">Account</span>
        <a href="#payments"><?= icon('receipt') ?>Payments</a>
        <a href="#notifications"><?= icon('bell') ?>Notifications</a>
        <a href="<?= e(url('/guest/export')) ?>"><?= icon('download') ?>Export data</a>
      </nav>
      <form method="POST" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="si-btn si-btn--outline si-btn--block" type="submit"><?= icon('log-out') ?>Sign out</button></form>
    </aside>

    <div class="si-dashboard-main">
      <div class="si-dashboard-topbar"><div class="si-dashboard-search" role="search"><?= icon('search') ?><input type="search" placeholder="Search trips, saved stays, destinations…" aria-label="Guest dashboard search"><span class="si-command-kbd">⌘K</span></div><a class="si-btn si-btn--primary si-btn--sm" href="<?= e(url('/search')) ?>"><?= icon('search','si-icon--sm') ?>Find a stay</a></div>
      <nav class="si-mobile-section-nav"><a class="si-btn si-btn--outline si-btn--sm" href="#trips">Trips</a><a class="si-btn si-btn--outline si-btn--sm" href="#saved">Saved</a><a class="si-btn si-btn--outline si-btn--sm" href="#payments">Payments</a></nav>

      <header class="si-dashboard-hero"><div><p class="si-eyebrow">Personal travel concierge</p><h1>Welcome back, <?= e($firstName) ?>.</h1><p><?= $nextTrip ? 'Your next stay is ready. Review dates, receipt and travel details before you go.' : 'Plan your next distinctive stay across Tanzania, manage receipts and revisit saved places.' ?></p></div><div class="si-filter-pills"><span class="is-active"><?= e(count($upcoming)) ?> upcoming</span><span><?= e(count($past)) ?> past</span><span><?= e(count($saved)) ?> saved</span></div></header>

      <?php if ($nextTrip): ?>
      <article class="si-trip-card" aria-labelledby="next-trip-title">
        <div class="si-trip-card__media"><img src="<?= e(image_url($nextTrip['cover_image'] ?? null, stayin_image_asset((string) (($nextTrip['property_region'] ?? '') . ' ' . ($nextTrip['property_name'] ?? 'Upcoming stay'))))) ?>" alt="<?= e($nextTrip['property_name'] ?? 'Upcoming stay') ?>" loading="eager" width="640" height="480"></div>
        <div class="si-trip-card__body">
          <div><p class="si-eyebrow">Your next stay</p><h2 id="next-trip-title" class="si-title si-title--lg" style="margin:.25rem 0"><?= e($nextTrip['property_name']) ?></h2><p class="si-caption"><?= icon('map-pin','si-icon--sm') ?> <?= e(trim(($nextTrip['district'] ?? '').' '.($nextTrip['region'] ?? ''))) ?></p></div>
          <div class="si-trip-meta"><div><span>Check-in</span><strong><?= e(format_date($nextTrip['check_in'])) ?></strong></div><div><span>Check-out</span><strong><?= e(format_date($nextTrip['check_out'])) ?></strong></div><div><span>Guests</span><strong><?= e($nextTrip['guests']) ?></strong></div><div><span>Booking</span><strong><?= e($nextTrip['booking_reference']) ?></strong></div></div>
          <div class="si-timeline" aria-label="Trip timeline"><?php foreach ($timeline as $i=>$label): ?><div class="si-timeline__step <?= $i < $currentStage ? 'is-done' : ($i === $currentStage ? 'is-current' : '') ?>"><span class="si-timeline__dot"></span><span><?= e($label) ?></span></div><?php endforeach; ?></div>
          <div class="si-filter-pills"><a class="is-active" href="<?= e(url('/checkout/'.$nextTrip['booking_reference'])) ?>">View booking</a><?php if (in_array($nextTrip['payment_status'], ['paid','refunded'], true)): ?><a href="<?= e(url('/bookings/'.$nextTrip['booking_reference'].'/receipt')) ?>">Download receipt</a><?php endif; ?><a href="<?= e(url('/property/'.$nextTrip['slug'])) ?>">View property</a></div>
        </div>
      </article>
      <?php else: ?>
      <div class="si-empty si-empty--action"><h2>Add your next trip</h2><p>Search handpicked stays and save your favourites before booking.</p><a class="si-btn si-btn--primary" href="<?= e(url('/search')) ?>"><?= icon('search') ?>Search stays</a></div>
      <?php endif; ?>

      <div class="si-kpi-grid" aria-label="Travel insights">
        <article class="si-kpi-card"><span class="si-kpi-card__label">Trips</span><strong class="si-kpi-card__value"><?= e($travelStats['trips'] ?? 0) ?></strong><span class="si-kpi-card__meta">All-time bookings</span></article>
        <article class="si-kpi-card"><span class="si-kpi-card__label">Nights stayed</span><strong class="si-kpi-card__value"><?= e($travelStats['nights'] ?? 0) ?></strong><span class="si-kpi-card__meta">Check-out minus check-in</span></article>
        <article class="si-kpi-card"><span class="si-kpi-card__label">Travel spend</span><strong class="si-kpi-card__value"><?= format_money($travelStats['spend'] ?? 0, $travelStats['currency'] ?? 'TZS') ?></strong><span class="si-kpi-card__meta">Paid/refunded booking totals</span></article>
        <article class="si-kpi-card"><span class="si-kpi-card__label">Saved stays</span><strong class="si-kpi-card__value"><?= e(count($saved)) ?></strong><span class="si-kpi-card__meta">Wishlist items</span></article>
      </div>

      <section id="trips" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>My bookings</h2><p>Upcoming, current, past and cancelled stays.</p></div><div class="si-filter-pills"><span class="is-active">Upcoming <?= e(count($upcoming)) ?></span><span>Past <?= e(count($past)) ?></span><span>Cancelled <?= e(count($cancelled)) ?></span></div></div><div class="si-dash-panel__body"><div class="si-section-grid"><?php foreach (array_slice($bookings,0,8) as $booking): ?><article class="si-attention-item"><span class="si-priority-dot <?= ($booking['status'] ?? '')==='confirmed'?'':'si-priority-dot--urgent' ?>"></span><div><strong><?= e($booking['property_name'] ?? 'Stay') ?></strong><p class="si-caption" style="margin:.15rem 0 0"><?= e(format_date($booking['check_in'])) ?> – <?= e(format_date($booking['check_out'])) ?> · <?= e($booking['booking_reference']) ?> · <?= format_money($booking['total_amount'],$booking['currency']) ?></p></div><span class="si-badge <?= ($booking['status'] ?? '')==='confirmed'?'si-badge--premium':(($booking['status'] ?? '')==='completed'?'si-badge--info':'si-badge--outline') ?>"><?= e(ucfirst(str_replace('_',' ',$booking['status'] ?? 'pending'))) ?></span></article><?php endforeach; if(empty($bookings)): ?><div class="si-empty"><h3>No bookings yet</h3><p>Your booked stays will appear here.</p></div><?php endif; ?></div></div></section>

      <?php if (!empty($saved)): ?><section id="saved" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Saved properties</h2><p>Premium wishlist grouped around places you may book later.</p></div><a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/guest/favourites')) ?>">View all</a></div><div class="si-dash-panel__body"><div class="si-grid-cards"><?php foreach (array_slice($saved,0,6) as $property): ?><?= View::component('property-card', ['property' => $property]) ?><?php endforeach; ?></div></div></section><?php endif; ?>

      <section id="payments" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Payments & receipts</h2><p>Receipts are generated immediately and opened for preview after payment confirmation.</p></div></div><div class="si-table-wrap si-table-wrap--premium"><table class="si-table si-table--premium"><thead><tr><th>Booking</th><th>Property</th><th>Date</th><th>Method</th><th class="si-table__num">Amount</th><th>Status</th><th>Receipt</th></tr></thead><tbody><?php foreach (array_slice($payments,0,12) as $pay): ?><tr><td><?= e($pay['booking_reference'] ?? '—') ?></td><td><?= e($pay['property_name'] ?? '—') ?></td><td><?= e(format_date($pay['created_at'] ?? '')) ?></td><td><?= e($pay['payment_method'] ?? $pay['provider'] ?? '—') ?></td><td class="si-table__num"><?= format_money($pay['amount'] ?? 0,$pay['currency'] ?? 'TZS') ?></td><td><span class="si-badge <?= ($pay['status'] ?? '')==='completed'?'si-badge--info':(($pay['status'] ?? '')==='failed'?'si-badge--danger':'si-badge--outline') ?>"><?= e(ucfirst($pay['status'] ?? 'unknown')) ?></span></td><td><?php if (!empty($pay['booking_reference']) && in_array($pay['status'] ?? '', ['completed','refunded'], true)): ?><a class="si-link" href="<?= e(url('/bookings/'.$pay['booking_reference'].'/receipt')) ?>" target="_blank" rel="noopener">Preview receipt</a><?php else: ?><span class="si-caption">Unavailable</span><?php endif; ?></td></tr><?php endforeach; if(empty($payments)): ?><tr><td colspan="7"><div class="si-empty"><h3>No payments yet</h3><p>Completed payments and receipts will appear here.</p></div></td></tr><?php endif; ?></tbody></table></div></section>

      <section id="notifications" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Notification centre</h2><p>Booking, payment, security and system updates.</p></div></div><div class="si-dash-panel__body"><div class="si-attention-list"><?php foreach (array_slice($notifications,0,8) as $n): ?><article class="si-attention-item" style="<?= !empty($n['is_read']) ? 'opacity:.72' : '' ?>"><span class="si-priority-dot"></span><div><strong><?= e($n['title_en'] ?? $n['type'] ?? 'Notification') ?></strong><p class="si-caption" style="margin:.15rem 0 0"><?= e($n['message_en'] ?? $n['message'] ?? '') ?> · <?= e(format_date($n['created_at'] ?? '', 'j M Y H:i')) ?></p></div><?php if (empty($n['is_read'])): ?><form method="POST" action="<?= e(url('/guest/notifications/read')) ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($n['id']) ?>"><button class="si-btn si-btn--outline si-btn--sm" type="submit">Mark read</button></form><?php endif; ?></article><?php endforeach; if(empty($notifications)): ?><div class="si-empty"><h3>You’re all caught up</h3><p>No current notifications.</p></div><?php endif; ?></div></div></section>
    </div>
  </div>
</section>
<?php View::stop() ?>
