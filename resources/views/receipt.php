<?php
/** @var array $booking */

use App\Core\View;

$snapshot = $booking['snapshot'] ?? null;
if (is_string($snapshot) && $snapshot !== '') {
    $decoded = json_decode($snapshot, true);
    $snapshot = is_array($decoded) ? $decoded : [];
}
if (!is_array($snapshot)) {
    $snapshot = [];
}

$reference = (string) ($booking['booking_reference'] ?? '');
$currency = (string) ($booking['currency'] ?? ($snapshot['currency'] ?? 'TZS'));
$status = (string) ($booking['status'] ?? 'confirmed');
$paymentStatus = (string) ($booking['payment_status'] ?? 'paid');
$rooms = (int) ($booking['rooms'] ?? ($snapshot['rooms'] ?? 1));
$guests = (int) ($booking['guests'] ?? ($snapshot['guests'] ?? 1));
$nights = 0;
if (!empty($booking['check_in']) && !empty($booking['check_out'])) {
    $in = strtotime((string) $booking['check_in']);
    $out = strtotime((string) $booking['check_out']);
    if ($in !== false && $out !== false && $out > $in) {
        $nights = (int) (($out - $in) / 86400);
    }
}
$subtotal = array_key_exists('subtotal', $booking) ? (float) $booking['subtotal'] : (float) (($snapshot['subtotal_minor'] ?? 0) / 100);
$total = array_key_exists('total_amount', $booking) ? (float) $booking['total_amount'] : (float) (($snapshot['total_minor'] ?? 0) / 100);
$fees = (float) (($snapshot['fees_minor'] ?? 0) / 100);
$tax = (float) (($snapshot['tax_minor'] ?? 0) / 100);
$discount = (float) (($snapshot['discount_minor'] ?? 0) / 100);

View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-md);">
    <div class="si-dash-panel" style="overflow:hidden;">
      <div class="si-dashboard-hero" style="margin:0;border-radius:0;">
        <div>
          <p class="si-eyebrow">StayIn receipt preview</p>
          <h1>Receipt <?= e($reference) ?></h1>
          <p>Confirmation for your stay at <?= e($booking['property_name'] ?? 'your selected property') ?>.</p>
        </div>
        <div class="si-filter-pills">
          <span class="is-active"><?= e(ucfirst(str_replace('_', ' ', $paymentStatus))) ?></span>
          <span><?= e(ucfirst(str_replace('_', ' ', $status))) ?></span>
        </div>
      </div>

      <div class="si-dash-panel__body" style="display:grid;gap:var(--si-space-6);">
        <div class="si-kpi-grid">
          <div class="si-kpi-card">
            <span class="si-kpi-card__label">Total paid</span>
            <strong class="si-kpi-card__value"><?= format_money($total, $currency) ?></strong>
          </div>
          <div class="si-kpi-card">
            <span class="si-kpi-card__label">Stay dates</span>
            <strong class="si-kpi-card__value" style="font-size:1.2rem;"><?= e(format_date($booking['check_in'] ?? '')) ?> – <?= e(format_date($booking['check_out'] ?? '')) ?></strong>
          </div>
          <div class="si-kpi-card">
            <span class="si-kpi-card__label">Guests / rooms</span>
            <strong class="si-kpi-card__value" style="font-size:1.2rem;"><?= e($guests) ?> guest<?= $guests === 1 ? '' : 's' ?> · <?= e($rooms) ?> room<?= $rooms === 1 ? '' : 's' ?></strong>
          </div>
        </div>

        <section class="si-dash-panel" aria-labelledby="receipt-details-title">
          <div class="si-dash-panel__head">
            <div>
              <h2 id="receipt-details-title">Booking details</h2>
              <p>This receipt is generated immediately from the current booking record. Amounts are shown in <?= e($currency) ?>.</p>
            </div>
            <button class="si-btn si-btn--outline si-btn--sm" type="button" onclick="window.print()"><?= icon('download', 'si-icon--sm') ?>Print / download PDF</button>
          </div>
          <div class="si-table-wrap si-table-wrap--premium">
            <table class="si-table si-table--premium">
              <tbody>
                <tr><th scope="row">Booking reference</th><td><?= e($reference) ?></td></tr>
                <tr><th scope="row">Property</th><td><?= e($booking['property_name'] ?? '—') ?></td></tr>
                <tr><th scope="row">Room</th><td><?= e($booking['room_name'] ?? '—') ?></td></tr>
                <tr><th scope="row">Check-in</th><td><?= e(format_date($booking['check_in'] ?? '')) ?></td></tr>
                <tr><th scope="row">Check-out</th><td><?= e(format_date($booking['check_out'] ?? '')) ?></td></tr>
                <tr><th scope="row">Length of stay</th><td><?= e($nights > 0 ? $nights . ' night' . ($nights === 1 ? '' : 's') : '—') ?></td></tr>
                <tr><th scope="row">Booking status</th><td><span class="si-badge si-badge--premium"><?= e(ucfirst(str_replace('_', ' ', $status))) ?></span></td></tr>
                <tr><th scope="row">Payment status</th><td><span class="si-badge si-badge--info"><?= e(ucfirst(str_replace('_', ' ', $paymentStatus))) ?></span></td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="si-dash-panel" aria-labelledby="receipt-summary-title">
          <div class="si-dash-panel__head"><div><h2 id="receipt-summary-title">Payment summary</h2><p>Confirmed charges for this booking.</p></div></div>
          <div class="si-dash-panel__body">
            <div class="payment-summary" style="max-width:560px;margin-inline-start:auto;">
              <div class="payment-row"><span>Accommodation</span><span><?= format_money($subtotal, $currency) ?></span></div>
              <?php if ($fees > 0): ?><div class="payment-row"><span>Fees</span><span><?= format_money($fees, $currency) ?></span></div><?php endif; ?>
              <?php if ($tax > 0): ?><div class="payment-row"><span>Taxes</span><span><?= format_money($tax, $currency) ?></span></div><?php endif; ?>
              <?php if ($discount > 0): ?><div class="payment-row"><span>Discount</span><span>-<?= format_money($discount, $currency) ?></span></div><?php endif; ?>
              <div class="payment-row payment-row--total"><strong>Total</strong><strong><?= format_money($total, $currency) ?></strong></div>
            </div>
          </div>
        </section>

        <div class="si-filter-pills">
          <a class="si-btn si-btn--primary" href="<?= e(url('/guest/bookings')) ?>"><?= icon('calendar', 'si-icon--sm') ?>Back to bookings</a>
          <a class="si-btn si-btn--outline" href="<?= e(url('/guest')) ?>#payments"><?= icon('receipt', 'si-icon--sm') ?>Payments</a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php View::stop() ?>