<?php
/** @var array $checkout */
/** @var string $csrfToken */
/** @var string $step */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-md);">
    <div class="checkout-layout">
      <main class="checkout-main">
        <h1>Checkout</h1>

        <div class="checkout-steps" style="display:flex;gap:var(--space-3);margin-bottom:var(--space-5);">
          <div class="step <?= $step === 'details' ? 'step--active' : 'step--complete' ?>">
            <span class="step__num">1</span> Guest details
          </div>
          <div class="step <?= $step === 'payment' ? 'step--active' : '' ?>">
            <span class="step__num">2</span> Payment
          </div>
          <div class="step">
            <span class="step__num">3</span> Confirmation
          </div>
        </div>

        <?php if ($step === 'details'): ?>
          <form method="POST" action="/checkout" class="checkout-form" data-validate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="property_id" value="<?= e($checkout['property_id'] ?? 0) ?>">
            <input type="hidden" name="room_type_id" value="<?= e($checkout['room_type_id'] ?? 0) ?>">
            <input type="hidden" name="check_in" value="<?= e($checkout['check_in'] ?? '') ?>">
            <input type="hidden" name="check_out" value="<?= e($checkout['check_out'] ?? '') ?>">

            <div class="form-row"><input type="text" name="guest_name" required placeholder="Full name" value="<?= e(old('guest_name')) ?>" class="input"></div>
            <div class="form-row"><input type="email" name="guest_email" required placeholder="Email" value="<?= e(old('guest_email')) ?>" class="input"></div>
            <div class="form-row"><input type="tel" name="guest_phone" required placeholder="Phone" value="<?= e(old('guest_phone')) ?>" class="input"></div>

            <button type="submit" class="btn btn--primary btn--block">Continue to payment</button>
          </form>
        <?php elseif ($step === 'payment'): ?>
          <form method="POST" action="/checkout" class="checkout-form" data-validate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="property_id" value="<?= e($checkout['property_id'] ?? 0) ?>">
            <input type="hidden" name="step" value="payment">

            <h2>Mock payment</h2>
            <p style="color:var(--warning);font-weight:600;">Test mode is enabled — no real payment will be processed.</p>

            <div class="form-row"><input type="text" name="card_number" required placeholder="Card number" class="input"></div>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:var(--space-3);">
              <div class="form-row"><input type="text" name="card_expiry" required placeholder="MM/YY" class="input"></div>
              <div class="form-row"><input type="text" name="card_cvc" required placeholder="CVC" class="input"></div>
            </div>
            <div class="form-row"><input type="text" name="cardholder" required placeholder="Cardholder name" class="input"></div>

            <button type="submit" class="btn btn--primary btn--block">Confirm & pay</button>
          </form>
        <?php endif; ?>
      </main>

      <aside class="checkout-sidebar">
        <div class="card" style="padding:var(--space-3);">
          <h3 style="margin-top:0;">Booking summary</h3>
          <div class="payment-summary">
            <div class="payment-row"><span>Accommodation</span><span>$<?= e(number_format((float) ($checkout['subtotal'] ?? 0), 2)) ?></span></div>
            <div class="payment-row"><span>Taxes & fees</span><span>$<?= e(number_format((float) ($checkout['taxes'] ?? 0), 2)) ?></span></div>
            <div class="payment-row payment-row--total"><strong>Total</strong><strong>$<?= e(number_format((float) ($checkout['total'] ?? 0), 2)) ?></strong></div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php View::stop() ?>
