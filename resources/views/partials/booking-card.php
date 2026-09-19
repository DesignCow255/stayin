<?php /** @var array|null $property */ /** @var array|null $room */ /** @var array $authUser */ $price = (float) ($property['min_price'] ?? 0); $currency = $property['currency'] ?? 'TZS'; $hostInitials = ((string) ($property['first_name'] ?? '') ?: 'H') . ((string) ($property['last_name'] ?? '') ?: 'ost'); ?>
<div class="booking-card">
  <div class="booking-card__price">
    <span class="price-value__amount price-value--large"><?= format_money($price, $currency) ?></span>
    <span class="price-value__note">/ night</span>
  </div>
  <?php if ($currency !== 'TZS'): ?>
  <div class="booking-card__equiv">≈ <?= format_money($price * 2643.562, 'TZS') ?> Tshs per night</div>
  <?php endif; ?>
  <form action="/bookings/hold" method="POST" class="booking-form" id="bookingForm">
    <?= csrf_field() ?>
    <?php if ($room): ?><input type="hidden" name="room_id" value="<?= e((int) $room['id']) ?>"><?php endif; ?>
    <div class="booking-form__row">
      <label><span class="field-label">Check in</span><input type="date" name="check_in" min="<?= e(gmdate('Y-m-d')) ?>" required class="booking-input" id="checkIn"></label>
      <label><span class="field-label">Check out</span><input type="date" name="check_out" min="<?= e(gmdate('Y-m-d', strtotime('+1 day'))) ?>" required class="booking-input" id="checkOut"></label>
    </div>
    <div class="booking-form__row booking-form__row--inline">
      <label><span class="field-label">Guests</span><select name="guests" class="booking-input" aria-label="Number of guests">
        <?php for ($i = 1; $i <= 10; $i++): ?><option value="<?= e($i) ?>"<?= $i === 1 ? ' selected' : '' ?>><?= e($i) ?></option><?php endfor; ?>
      </select></label>
      <label><span class="field-label">Rooms</span><select name="rooms" class="booking-input" aria-label="Number of rooms">
        <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= e($i) ?>"<?= $i === 1 ? ' selected' : '' ?>><?= e($i) ?></option><?php endfor; ?>
      </select></label>
    </div>
    <button type="submit" class="btn btn--primary btn--block" id="holdButton"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Hold your stay</button>
  </form>
  <?php if (empty($authUser)): ?>
  <p class="booking-card__note text-muted"><a href="/login">Sign in</a> to hold and pay for this stay.</p>
  <?php else: ?>
  <p class="booking-card__note text-muted">You'll pay when you confirm the booking. Free cancellation before payment.</p>
  <?php endif; ?>
</div>
<?php if (!empty($property['host_user_id'])): ?>
<div class="host-card">
  <div class="host-card__header">
    <div class="host-card__avatar"><?= strtoupper(substr($hostInitials, 0, 2)) ?></div>
    <div>
      <div class="host-card__name"><?= e($property['first_name'] ?? '') ?> <?= e($property['last_name'] ?? '') ?></div>
      <div class="text-muted">Host</div>
    </div>
  </div>
</div>
<?php endif; ?>