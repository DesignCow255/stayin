<?php
/** @var array $properties */ /** @var array $bookings */ /** @var array $upcoming */ /** @var array $propertyPerformance */ /** @var array $availability */ /** @var array $statusMix */ /** @var array $revenueTrend */ /** @var array $balances */ /** @var array $settlements */ /** @var array $revenue */
use App\Core\View;
use App\Services\AuthService;
View::start('content');
$viewer = AuthService::user() ?? [];
$hostName = trim((string)(($viewer['first_name'] ?? '') . ' ' . ($viewer['last_name'] ?? '')));
$totalRevenue = 0.0; $primaryCurrency = 'TZS'; $abv = 0.0;
foreach ($revenue as $r) { $totalRevenue += (float)($r['total'] ?? 0); $primaryCurrency = $r['currency'] ?? $primaryCurrency; $abv=max($abv,(float)($r['average_booking_value'] ?? 0)); }
$balance = 0.0; foreach ($balances as $b) { $balance += ((float)($b['balance_minor'] ?? 0))/100; $primaryCurrency = $b['currency'] ?? $primaryCurrency; }
$totalBookingCount = max(1,count($bookings));
$confirmed = count(array_filter($bookings, static fn($b) => ($b['status'] ?? '') === 'confirmed'));
$cancelled = count(array_filter($bookings, static fn($b) => ($b['status'] ?? '') === 'cancelled'));
$ratings = array_filter(array_map(static fn($p) => (float)($p['rating'] ?? 0), $properties), static fn($r) => $r > 0);
$avgRating = $ratings ? array_sum($ratings)/count($ratings) : 0;
$maxTrend = max(array_map(static fn($r) => (float)($r['total'] ?? 0), $revenueTrend ?: [['total'=>1]]));
$availabilityTotal = max(1,array_sum(array_map(static fn($r)=>(int)($r['nights']??0),$availability)));
?>
<section class="si-app-dashboard">
  <div class="si-dashboard-shell">
    <aside class="si-dashboard-sidebar" aria-label="Host navigation">
      <div><p class="si-eyebrow">Host suite</p><strong><?= e($hostName ?: 'Host') ?></strong><p class="si-caption">Business performance and property operations.</p></div>
      <nav class="si-dashboard-nav">
        <span class="si-dashboard-nav__label">Workspace</span>
        <a href="<?= e(url('/host')) ?>" aria-current="page"><?= icon('house') ?>Overview</a>
        <a href="<?= e(url('/host/properties')) ?>"><?= icon('building') ?>Properties</a>
        <a href="<?= e(url('/host/bookings')) ?>"><?= icon('calendar') ?>Bookings</a>
        <span class="si-dashboard-nav__label">Performance</span>
        <a href="#revenue"><?= icon('chart') ?>Revenue</a>
        <a href="#calendar"><?= icon('calendar') ?>Availability</a>
        <a href="#payouts"><?= icon('wallet') ?>Payouts</a>
        <a href="<?= e(url('/exports/bookings')) ?>"><?= icon('download') ?>Reports</a>
      </nav>
      <a class="si-btn si-btn--primary si-btn--block" href="<?= e(url('/host/properties/create')) ?>"><?= icon('plus') ?>Add property</a>
      <form method="POST" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="si-btn si-btn--outline si-btn--block" type="submit"><?= icon('log-out') ?>Sign out</button></form>
    </aside>

    <div class="si-dashboard-main">
      <div class="si-dashboard-topbar"><div class="si-dashboard-search" role="search"><?= icon('search') ?><input type="search" placeholder="Search bookings, guests, properties…" aria-label="Host dashboard search"><span class="si-command-kbd">⌘K</span></div><div class="si-filter-pills"><span class="is-active">30 Days</span><span>90 Days</span><span>Year</span></div></div>
      <nav class="si-mobile-section-nav"><a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/host/properties')) ?>">Properties</a><a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/host/bookings')) ?>">Bookings</a><a class="si-btn si-btn--outline si-btn--sm" href="#payouts">Payouts</a></nav>

      <header class="si-dashboard-hero"><div><p class="si-eyebrow">Business overview</p><h1>Good morning, <?= e($hostName ?: 'Host') ?>.</h1><p>Here's how your properties are performing. Metrics come from your listings, bookings, availability, balances and settlements.</p></div><div class="si-filter-pills"><span class="is-active">All properties</span><?php foreach (array_slice($properties,0,3) as $p): ?><span><?= e($p['name']) ?></span><?php endforeach; ?></div></header>

      <div class="si-kpi-grid">
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Revenue</span><?= icon('wallet') ?></div><strong class="si-kpi-card__value"><?= format_money($totalRevenue,$primaryCurrency) ?></strong><span class="si-kpi-card__meta">Paid/refunded stays</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Upcoming Earnings</span><?= icon('bank') ?></div><strong class="si-kpi-card__value"><?= format_money($balance,$primaryCurrency) ?></strong><span class="si-kpi-card__meta">Ledger balance if available</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Bookings</span><?= icon('calendar') ?></div><strong class="si-kpi-card__value"><?= e(count($bookings)) ?></strong><span class="si-kpi-card__meta"><?= e($confirmed) ?> confirmed</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Average Booking Value</span><?= icon('receipt') ?></div><strong class="si-kpi-card__value"><?= format_money($abv,$primaryCurrency) ?></strong><span class="si-kpi-card__meta">Revenue ÷ paid bookings</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Cancellation Rate</span><?= icon('alert') ?></div><strong class="si-kpi-card__value"><?= e(round(($cancelled/$totalBookingCount)*100,1)) ?>%</strong><span class="si-kpi-card__meta">Cancelled ÷ total bookings</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Guest Rating</span><?= icon('star') ?></div><strong class="si-kpi-card__value"><?= e($avgRating ? number_format($avgRating,2) : '—') ?></strong><span class="si-kpi-card__meta">Average of rated properties</span></article>
      </div>

      <div class="si-section-grid si-section-grid--2">
        <section id="revenue" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Revenue performance</h2><p>Last 14 days of confirmed financial demand.</p></div><span class="si-badge si-badge--outline">Daily</span></div><div class="si-dash-panel__body"><div class="si-chart" role="img" aria-label="Host revenue trend"><?php foreach ($revenueTrend as $r): $h=$maxTrend>0?max(8,((float)$r['total']/$maxTrend)*100):8; ?><div class="si-chart__bar" tabindex="0" style="height:<?= e($h) ?>%"><span><?= e(format_date($r['day'] ?? '', 'M j')) ?> · <?= e(format_money($r['total'] ?? 0,$r['currency'] ?? $primaryCurrency)) ?></span></div><?php endforeach; ?></div><p class="si-caption">Revenue breakdown into fees, taxes, commission and net host earnings requires consistent ledger exposure.</p></div></section>
        <section id="calendar" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Occupancy & availability</h2><p>Availability blocks for the next 30 days.</p></div></div><div class="si-dash-panel__body"><div class="si-meter-list"><?php foreach ($availability as $row): $pct=((int)$row['nights']/$availabilityTotal)*100; ?><div class="si-meter"><div class="si-meter__row"><span><?= e(ucfirst($row['status'])) ?> nights</span><strong><?= e($row['nights']) ?></strong></div><div class="si-meter__track"><span class="si-meter__fill" style="--value:<?= e($pct) ?>%"></span></div></div><?php endforeach; if(empty($availability)): ?><div class="si-empty si-empty--action"><h3>No availability blocks yet</h3><p>Add room inventory from property management to unlock occupancy analytics.</p></div><?php endif; ?></div></div></section>
      </div>

      <section class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Upcoming arrivals</h2><p>Operational view for the stays that need your attention next.</p></div><a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/host/bookings')) ?>">View bookings</a></div><div class="si-table-wrap si-table-wrap--premium"><table class="si-table si-table--premium"><thead><tr><th>Guest</th><th>Property</th><th>Room</th><th>Arrival</th><th>Departure</th><th>Guests</th><th>Status</th><th class="si-table__num">Total</th><th>Receipt</th></tr></thead><tbody><?php foreach ($upcoming as $b): ?><tr><td><?= e(trim(($b['first_name'] ?? '').' '.($b['last_name'] ?? ''))) ?><br><span class="si-caption"><?= e($b['booking_reference']) ?></span></td><td><?= e($b['name']) ?></td><td><?= e($b['room_name']) ?></td><td><?= e(format_date($b['check_in'])) ?></td><td><?= e(format_date($b['check_out'])) ?></td><td><?= e($b['guests']) ?></td><td><span class="si-badge <?= $b['status']==='confirmed'?'si-badge--premium':'si-badge--warning' ?>"><?= e(ucfirst(str_replace('_',' ',$b['status']))) ?></span></td><td class="si-table__num"><?= format_money($b['total_amount'],$b['currency']) ?></td><td><?php if (in_array($b['payment_status'] ?? '', ['paid','refunded'], true)): ?><a class="si-link" href="<?= e(url('/bookings/'.$b['booking_reference'].'/receipt')) ?>" target="_blank" rel="noopener">Preview</a><?php else: ?><span class="si-caption">Unavailable</span><?php endif; ?></td></tr><?php endforeach; if(empty($upcoming)): ?><tr><td colspan="9"><div class="si-empty"><h3>No upcoming arrivals</h3><p>New confirmed bookings will appear here automatically.</p></div></td></tr><?php endif; ?></tbody></table></div></section>

      <section class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Property performance</h2><p>Compare your properties without declaring arbitrary winners.</p></div></div><div class="si-table-wrap si-table-wrap--premium"><table class="si-table si-table--premium"><thead><tr><th>Property</th><th>Region</th><th>Status</th><th class="si-table__num">Bookings</th><th class="si-table__num">Revenue</th><th class="si-table__num">Avg stay</th><th class="si-table__num">Rating</th></tr></thead><tbody><?php foreach ($propertyPerformance as $p): ?><tr><td><strong><?= e($p['name']) ?></strong><br><span class="si-caption">#<?= e($p['id']) ?></span></td><td><?= e($p['region'] ?: '—') ?></td><td><span class="si-badge <?= $p['status']==='published'?'si-badge--premium':($p['status']==='pending'?'si-badge--warning':'si-badge--outline') ?>"><?= e(ucfirst($p['status'])) ?></span></td><td class="si-table__num"><?= e($p['bookings']) ?></td><td class="si-table__num"><?= format_money($p['revenue'],$p['currency']) ?></td><td class="si-table__num"><?= e($p['average_stay'] ? number_format((float)$p['average_stay'],1).' nights' : '—') ?></td><td class="si-table__num"><?= e($p['rating'] ?: '—') ?></td></tr><?php endforeach; ?></tbody></table></div></section>

      <section id="payouts" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Payouts & settlements</h2><p>Financial clarity for balances and host settlement records.</p></div></div><div class="si-dash-panel__body"><div class="si-kpi-grid"><div class="si-kpi-card"><span class="si-kpi-card__label">Available balance</span><strong class="si-kpi-card__value"><?= format_money($balance,$primaryCurrency) ?></strong></div><div class="si-kpi-card"><span class="si-kpi-card__label">Settlements</span><strong class="si-kpi-card__value"><?= e(count($settlements)) ?></strong></div><div class="si-kpi-card"><span class="si-kpi-card__label">Backend required</span><p class="si-caption">Tax documents, payment methods and payout scheduling need dedicated routes.</p></div></div></div></section>
    </div>
  </div>
</section>
<?php View::stop() ?>
