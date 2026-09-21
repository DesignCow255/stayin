<?php
/** @var array $booking */
/** @var array|null $quote */

use App\Core\View;

$quote = is_array($quote ?? null) ? $quote : [];
$reference = (string) ($booking['booking_reference'] ?? '');
$currency = (string) ($booking['currency'] ?? ($quote['currency'] ?? 'TZS'));
$paymentStatus = (string) ($booking['payment_status'] ?? 'unpaid');
$bookingStatus = (string) ($booking['status'] ?? 'awaiting_payment');
$isPaid = in_array($paymentStatus, ['paid', 'refunded'], true);
$isPayable = !$isPaid && $bookingStatus === 'awaiting_payment' && !empty($booking['expires_at']) && strtotime((string) $booking['expires_at']) > time();
$step = $isPaid ? 'confirmation' : 'payment';
$rooms = (int) ($booking['rooms'] ?? ($quote['rooms'] ?? 1));
$guests = (int) ($booking['guests'] ?? ($quote['guests'] ?? 1));
$nights = 0;
if (!empty($booking['check_in']) && !empty($booking['check_out'])) {
    $in = strtotime((string) $booking['check_in']);
    $out = strtotime((string) $booking['check_out']);
    if ($in !== false && $out !== false && $out > $in) {
        $nights = (int) (($out - $in) / 86400);
    }
}
$subtotal = array_key_exists('subtotal', $booking) ? (float) $booking['subtotal'] : (float) (($quote['subtotal_minor'] ?? 0) / 100);
$fees = (float) (($quote['fees_minor'] ?? 0) / 100);
$tax = (float) (($quote['tax_minor'] ?? 0) / 100);
$discount = (float) (($quote['discount_minor'] ?? 0) / 100);
$total = array_key_exists('total_amount', $booking) ? (float) $booking['total_amount'] : (float) (($quote['total_minor'] ?? 0) / 100);

View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-lg);">
    <div class="checkout-layout">
      <main class="checkout-main">
        <div class="checkout-header">
          <p class="si-eyebrow">Secure checkout</p>
          <h1>Complete your booking</h1>
          <p class="checkout-header__ref">Reference <?= e($reference) ?></p>
        </div>

        <div class="checkout-steps" style="display:flex;gap:var(--space-3);margin-bottom:var(--space-5);">
          <div class="step step--complete"><span class="step__num">1</span> Hold created</div>
          <div class="step <?= $step === 'payment' ? 'step--active' : 'step--complete' ?>"><span class="step__num">2</span> Payment</div>
          <div class="step <?= $step === 'confirmation' ? 'step--active' : '' ?>"><span class="step__num">3</span> Confirmation</div>
        </div>

        <?php if ($isPaid): ?>
          <div class="si-alert si-alert--success" role="status">
            <?= icon('check') ?>
            <div><strong>Payment confirmed</strong><br>Your booking is confirmed. A receipt is available for your records.</div>
          </div>
          <div class="si-filter-pills" style="margin-block-start:var(--si-space-4);">
            <a class="si-btn si-btn--primary" href="<?= e(url('/bookings/' . $reference . '/receipt')) ?>" target="_blank" rel="noopener"><?= icon('receipt', 'si-icon--sm') ?>View receipt</a>
            <a class="si-btn si-btn--outline" href="<?= e(url('/guest/bookings')) ?>"><?= icon('calendar', 'si-icon--sm') ?>Back to bookings</a>
          </div>
        <?php elseif ($isPayable): ?>
          <div class="card" style="padding:var(--space-5);">
            <h2 style="margin-top:0;">Mock payment</h2>
            <p style="color:var(--warning);font-weight:600;">Test mode is enabled — no real payment will be processed.</p>
            <p class="si-caption">Your hold expires <?= e(format_date($booking['expires_at'] ?? '', 'j M Y H:i')) ?>. Accept the terms and choose a simulation result below.</p>

            <form method="POST" action="<?= e(url('/checkout/' . $reference . '/pay')) ?>" class="checkout-form" data-validate style="display:grid;gap:var(--si-space-4);margin-block-start:var(--si-space-4);">
              <?= csrf_field() ?>
              <label class="si-field" for="payment-scenario">
                <span>Payment simulation</span>
                <select id="payment-scenario" name="scenario" class="si-select">
                  <option value="success">Successful payment</option>
                  <option value="pending">Pending payment</option>
                  <option value="failed">Failed payment</option>
                </select>
              </label>
              <label style="display:flex;gap:var(--si-space-2);align-items:flex-start;">
                <input type="checkbox" name="terms" value="1" required>
                <span>I accept the booking terms, cancellation policy and mock payment disclosure.</span>
              </label>
              <button type="submit" class="si-btn si-btn--primary si-btn--block"><?= icon('lock', 'si-icon--sm') ?>Confirm & pay <?= format_money($total, $currency) ?></button>
            </form>
          </div>
        <?php else: ?>
          <div class="si-alert si-alert--warning" role="alert">
            <?= icon('error') ?>
            <div><strong>This booking is not payable</strong><br>The hold may have expired or the booking status no longer allows payment. Please start a new booking or contact support.</div>
          </div>
          <div class="si-filter-pills" style="margin-block-start:var(--si-space-4);">
            <a class="si-btn si-btn--primary" href="<?= e(url('/search')) ?>"><?= icon('search', 'si-icon--sm') ?>Find another stay</a>
            <a class="si-btn si-btn--outline" href="<?= e(url('/guest/bookings')) ?>">Back to bookings</a>
          </div>
        <?php endif; ?>
      </main>

      <aside class="checkout-sidebar">
        <div class="card" style="padding:var(--space-4);">
          <h3 style="margin-top:0;">Booking summary</h3>
          <div class="checkout-summary">
            <div class="checkout-summary__row"><span>Property</span><strong><?= e($booking['property_name'] ?? '—') ?></strong></div>
            <div class="checkout-summary__row"><span>Room</span><span><?= e($booking['room_name'] ?? '—') ?></span></div>
            <div class="checkout-summary__row"><span>Dates</span><span><?= e(format_date($booking['check_in'] ?? '')) ?> – <?= e(format_date($booking['check_out'] ?? '')) ?></span></div>
            <div class="checkout-summary__row"><span>Guests / rooms</span><span><?= e($guests) ?> guest<?= $guests === 1 ? '' : 's' ?> · <?= e($rooms) ?> room<?= $rooms === 1 ? '' : 's' ?></span></div>
            <div class="checkout-summary__row"><span>Accommodation<?= $nights > 0 ? ' · ' . e($nights) . ' night' . ($nights === 1 ? '' : 's') : '' ?></span><span><?= format_money($subtotal, $currency) ?></span></div>
            <?php if ($fees > 0): ?><div class="checkout-summary__row"><span>Fees</span><span><?= format_money($fees, $currency) ?></span></div><?php endif; ?>
            <?php if ($tax > 0): ?><div class="checkout-summary__row"><span>Taxes</span><span><?= format_money($tax, $currency) ?></span></div><?php endif; ?>
            <?php if ($discount > 0): ?><div class="checkout-summary__row"><span>Discount</span><span>-<?= format_money($discount, $currency) ?></span></div><?php endif; ?>
            <div class="checkout-summary__row total"><span>Total</span><span class="checkout-summary__value"><?= format_money($total, $currency) ?></span></div>
          </div>
          <p class="si-caption" style="margin-block-end:0;">Status: <?= e(ucfirst(str_replace('_', ' ', $bookingStatus))) ?> · <?= e(ucfirst(str_replace('_', ' ', $paymentStatus))) ?></p>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php View::stop() ?>