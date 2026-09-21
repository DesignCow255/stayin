<?php
/** @var array $counts */ /** @var array $financials */ /** @var array $revenueTrend */ /** @var array $bookingStatuses */ /** @var array $propertyRegions */ /** @var array $propertyPerformance */ /** @var array $needsAttention */ /** @var array $payments */ /** @var array $users */ /** @var array $audit */ /** @var array $rates */
use App\Core\View;
use App\Services\AuthService;
use App\Services\Gate;

View::start('content');
$viewer = AuthService::user() ?? [];
$role = (string) ($viewer['role'] ?? 'admin');
$isSuper = $role === 'super_admin';
$canProperties = $isSuper || Gate::allows('properties.view');
$canPayments = $isSuper || Gate::allows('payments.view');
$canUsers = $isSuper || Gate::allows('users.view');
$canAudit = $isSuper || Gate::allows('audit.view');
$canFinance = $isSuper || Gate::allows('finance.view');
$canCms = $isSuper || Gate::allows('cms.manage');
$canReports = $isSuper || Gate::allows('bookings.view') || Gate::allows('finance.view');
$adminName = trim((string) (($viewer['first_name'] ?? '') . ' ' . ($viewer['last_name'] ?? '')));
$totalGmv = 0.0; $primaryCurrency = 'TZS'; $abv = 0.0;
foreach ($financials as $f) { $totalGmv += (float)($f['gross_booking_value'] ?? 0); $primaryCurrency = $f['currency'] ?? $primaryCurrency; $abv = max($abv, (float)($f['average_booking_value'] ?? 0)); }
$maxTrend = max(array_map(static fn($r) => (float)($r['total'] ?? 0), $revenueTrend ?: [['total'=>1]]));
$statusTotal = max(1, array_sum(array_map(static fn($r) => (int)($r['total'] ?? 0), $bookingStatuses)));
?>
<section class="si-app-dashboard">
  <div class="si-dashboard-shell">
    <aside class="si-dashboard-sidebar" aria-label="Admin navigation">
      <div>
        <p class="si-eyebrow">StayIn OS</p>
        <strong>Admin command centre</strong>
        <p class="si-caption">Operational, financial and trust controls.</p>
      </div>
      <nav class="si-dashboard-nav">
        <span class="si-dashboard-nav__label">Dashboard</span>
        <a href="<?= e(url('/admin')) ?>" aria-current="page"><?= icon('chart') ?>Overview</a>
        <span class="si-dashboard-nav__label">Operations</span>
        <a href="#properties"><?= icon('building') ?>Properties</a>
        <a href="#users"><?= icon('users') ?>Users</a>
        <span class="si-dashboard-nav__label">Financial</span>
        <a href="#finance"><?= icon('credit-card') ?>Payments</a>
        <?php if ($canReports): ?><a href="<?= e(url('/exports/bookings')) ?>"><?= icon('download') ?>Export bookings</a><?php endif; ?>
        <?php if ($canCms): ?><a href="<?= e(url('/admin/content')) ?>"><?= icon('images') ?>Content studio</a><?php endif; ?>
      </nav>
      <form method="POST" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="si-btn si-btn--outline si-btn--block" type="submit"><?= icon('log-out') ?>Sign out</button></form>
    </aside>

    <div class="si-dashboard-main">
      <div class="si-dashboard-topbar">
        <div class="si-dashboard-search" role="search"><?= icon('search') ?><input type="search" placeholder="Search users, properties, bookings…" aria-label="Dashboard search"><span class="si-command-kbd">⌘K</span></div>
        <div class="si-filter-pills" aria-label="Date range"><span class="is-active">30 Days</span><span>Quarter</span><span>Year</span></div>
      </div>
      <nav class="si-mobile-section-nav" aria-label="Admin mobile sections"><a class="si-btn si-btn--outline si-btn--sm" href="#properties">Properties</a><a class="si-btn si-btn--outline si-btn--sm" href="#finance">Finance</a></nav>

      <header class="si-dashboard-hero">
        <div>
          <p class="si-eyebrow">Executive overview</p>
          <h1>Good morning, <?= e($adminName !== '' ? $adminName : 'Administrator') ?>.</h1>
          <p>Here's what is happening across the platform. Values are calculated from bookings, payments, users and property records.</p>
        </div>
        <div class="si-filter-pills"><span>vs previous period: backend comparison required</span></div>
      </header>

      <div class="si-kpi-grid" aria-label="Platform KPIs">
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Gross Booking Value</span><?= icon('wallet') ?></div><strong class="si-kpi-card__value"><?= format_money($totalGmv, $primaryCurrency) ?></strong><span class="si-kpi-card__meta">Paid/refunded bookings only</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Total Bookings</span><?= icon('calendar') ?></div><strong class="si-kpi-card__value"><?= e($counts['Bookings'] ?? 0) ?></strong><span class="si-kpi-card__meta"><span class="si-trend si-trend--neutral"><?= e($rates['confirmation_rate'] ?? 0) ?>%</span> confirmed</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Active Properties</span><?= icon('building') ?></div><strong class="si-kpi-card__value"><?= e($counts['Published properties'] ?? 0) ?></strong><span class="si-kpi-card__meta"><?= e($counts['Pending properties'] ?? 0) ?> awaiting moderation</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Active Hosts</span><?= icon('users') ?></div><strong class="si-kpi-card__value"><?= e($counts['Active hosts'] ?? 0) ?></strong><span class="si-kpi-card__meta">Verified supply partners</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Active Guests</span><?= icon('user') ?></div><strong class="si-kpi-card__value"><?= e($counts['Active guests'] ?? 0) ?></strong><span class="si-kpi-card__meta">Demand-side accounts</span></article>
        <article class="si-kpi-card"><div class="si-kpi-card__top"><span class="si-kpi-card__label">Average Booking Value</span><?= icon('receipt') ?></div><strong class="si-kpi-card__value"><?= format_money($abv, $primaryCurrency) ?></strong><span class="si-kpi-card__meta">Formula: paid booking value ÷ bookings</span></article>
      </div>

      <div class="si-section-grid si-section-grid--2">
        <section class="si-dash-panel" aria-labelledby="revenue-title"><div class="si-dash-panel__head"><div><h2 id="revenue-title">Revenue overview</h2><p>Daily gross booking value for the last 14 days.</p></div><span class="si-badge si-badge--outline">Real booking data</span></div><div class="si-dash-panel__body"><div class="si-chart" role="img" aria-label="Revenue trend chart"><?php foreach ($revenueTrend as $r): $h=$maxTrend>0?max(8,((float)$r['total']/$maxTrend)*100):8; ?><div class="si-chart__bar" tabindex="0" style="height:<?= e($h) ?>%"><span><?= e(format_date($r['day'] ?? '', 'M j')) ?> · <?= e(format_money($r['total'] ?? 0, $r['currency'] ?? $primaryCurrency)) ?></span></div><?php endforeach; ?></div><p class="si-caption">Net platform revenue, taxes, refunds and commission composition require ledger/commission rules to be exposed consistently.</p></div></section>
        <section class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Booking performance</h2><p>Status distribution across all bookings.</p></div><span class="si-badge si-badge--info">Cancellation <?= e($rates['cancellation_rate'] ?? 0) ?>%</span></div><div class="si-dash-panel__body"><div class="si-meter-list"><?php foreach ($bookingStatuses as $s): $pct=((int)$s['total']/$statusTotal)*100; ?><div class="si-meter"><div class="si-meter__row"><span><?= e(ucfirst(str_replace('_',' ', $s['status'] ?? 'unknown'))) ?></span><strong><?= e($s['total'] ?? 0) ?></strong></div><div class="si-meter__track"><span class="si-meter__fill" style="--value:<?= e($pct) ?>%"></span></div></div><?php endforeach; ?></div></div></section>
      </div>

      <section class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Needs attention</h2><p>Operational queue for property verification and payment exceptions.</p></div></div><div class="si-dash-panel__body"><div class="si-attention-list"><?php if (empty($needsAttention)): ?><div class="si-empty si-empty--action"><h3>No urgent exceptions</h3><p>Property moderation and payment exception queues are clear.</p></div><?php else: foreach ($needsAttention as $item): ?><article class="si-attention-item"><span class="si-priority-dot <?= ($item['priority'] ?? '') === 'Urgent' ? 'si-priority-dot--urgent' : '' ?>"></span><div><strong><?= e($item['title']) ?></strong><p class="si-caption" style="margin:.15rem 0 0"><?= e($item['label']) ?> · <?= e($item['meta']) ?> · <?= e($item['time']) ?></p></div><a class="si-btn si-btn--outline si-btn--sm" href="<?= e($item['href']) ?>"><?= e($item['action']) ?></a></article><?php endforeach; endif; ?></div></div></section>

      <?php if ($canProperties): ?><section id="properties" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Property performance</h2><p>Revenue, booking and trust indicators by property.</p></div><a class="si-btn si-btn--outline si-btn--sm" href="<?= e(url('/exports/bookings')) ?>"><?= icon('download','si-icon--sm') ?>Export</a></div><div class="si-table-wrap si-table-wrap--premium"><table class="si-table si-table--premium"><thead><tr><th>Property</th><th>Host</th><th>Location</th><th>Status</th><th class="si-table__num">Bookings</th><th class="si-table__num">Revenue</th><th class="si-table__num">Rating</th></tr></thead><tbody><?php foreach ($propertyPerformance as $p): ?><tr><td><strong><?= e($p['name']) ?></strong><br><span class="si-caption">#<?= e($p['id']) ?></span></td><td><?= e($p['host_name']) ?></td><td><?= e($p['region'] ?: '—') ?></td><td><span class="si-badge <?= ($p['status'] ?? '') === 'published' ? 'si-badge--premium' : (($p['status'] ?? '') === 'pending' ? 'si-badge--warning' : 'si-badge--outline') ?>"><?= e(ucfirst($p['status'] ?? 'unknown')) ?></span></td><td class="si-table__num"><?= e($p['bookings']) ?></td><td class="si-table__num"><?= format_money($p['revenue'], $p['currency']) ?></td><td class="si-table__num"><?= e($p['rating'] ?? '0.00') ?> ★</td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

      <?php if ($canPayments): ?><section id="finance" class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Financial management</h2><p>Recent payment transactions. Receipt previews are generated immediately from server records.</p></div></div><div class="si-table-wrap si-table-wrap--premium"><table class="si-table si-table--premium"><thead><tr><th>ID</th><th>Booking</th><th>Provider</th><th>Method</th><th class="si-table__num">Amount</th><th>Status</th><th>Created</th><th>Receipt</th></tr></thead><tbody><?php foreach (array_slice($payments,0,16) as $pay): ?><tr><td>#<?= e($pay['id']) ?></td><td><?= e($pay['booking_reference'] ?? $pay['booking_id'] ?? '—') ?></td><td><?= e($pay['provider']) ?></td><td><?= e($pay['payment_method'] ?? '—') ?></td><td class="si-table__num"><?= format_money($pay['amount'], $pay['currency']) ?></td><td><span class="si-badge <?= $pay['status']==='completed'?'si-badge--info':($pay['status']==='failed'?'si-badge--danger':'si-badge--outline') ?>"><?= e(ucfirst($pay['status'])) ?></span></td><td><?= e(format_date($pay['created_at'] ?? '', 'j M Y')) ?></td><td><?php if (!empty($pay['booking_reference']) && in_array($pay['status'] ?? '', ['completed','refunded'], true)): ?><a class="si-link" href="<?= e(url('/bookings/'.$pay['booking_reference'].'/receipt')) ?>" target="_blank" rel="noopener">Preview</a><?php else: ?><span class="si-caption">Unavailable</span><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>

      <section class="si-dash-panel"><div class="si-dash-panel__head"><div><h2>Backend roadmap surfaced, not faked</h2><p>These premium modules are visually planned but need backend implementation before controls are enabled.</p></div></div><div class="si-dash-panel__body"><div class="si-kpi-grid"><div class="si-kpi-card"><strong>Booking funnel</strong><p class="si-caption">Needs event tracking for Search → View → Checkout → Payment → Confirmed.</p></div><div class="si-kpi-card"><strong>Geographic analytics</strong><p class="si-caption">Property coordinates exist; clustering/map tile integration is required.</p></div><div class="si-kpi-card"><strong>Disputes & chargebacks</strong><p class="si-caption">No dedicated dispute/chargeback tables discovered.</p></div></div></div></section>
    </div>
  </div>
</section>
<?php View::stop() ?>
