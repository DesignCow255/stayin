<?php
/**
 * Sticky booking / inquiry panel (§11, §20, §21).
 *
 * @var array $property @var array $roomTypes @var array $selection
 * @var array|null $quote @var string|null $quoteError @var array|null $host
 */
$whatsappNumber = trim((string) config('support.whatsapp', ''));
$whatsappEnabled = $whatsappNumber !== '' && config('features.whatsapp_deeplink', false);
$propertyId = (int) ($property['id'] ?? 0);
$saved = $propertyId > 0 && is_favourite($propertyId);
$shareUrl = url('/property/' . $property['slug']);
$roomOptions = array_map(static fn (array $room): array => [
    'id' => (int) $room['id'],
    'label' => $room['name'] . ' · ' . format_money((float) $room['base_price'], (string) $room['currency']) . ' / night',
], $roomTypes);
?>
<div class="si-panel si-panel--sticky" id="availability">
  <p class="si-panel__price">
    <span class="si-panel__amount"><?= $roomTypes ? e(format_money((float) $roomTypes[0]['base_price'], (string) $roomTypes[0]['currency'])) : '' ?></span>
    <?php if ($roomTypes): ?><span class="si-caption">/ night, from</span><?php endif; ?>
  </p>
  <?php if ($quoteError): ?>
  <div class="si-alert si-alert--error" role="alert" style="margin-block-start:var(--si-space-3)"><?= icon('error') ?><span><?= e($quoteError) ?></span></div>
  <?php endif; ?>

  <?php if ($roomTypes): ?>
  <form action="<?= e(url('/property/' . $property['slug'] . '#availability')) ?>" method="GET" class="si-stack" data-validate<?= track('viewing_requested', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>>
    <div class="si-field">
      <label for="booking-room">Room type</label>
      <select class="si-select" id="booking-room" name="room_id">
        <?php foreach ($roomOptions as $option): ?>
        <option value="<?= e($option['id']) ?>"<?= $selection['room_id'] === $option['id'] ? ' selected' : '' ?>><?= e($option['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="si-field-row si-field-row--2">
      <div class="si-field">
        <label for="booking-in">Check in</label>
        <input class="si-input" id="booking-in" type="date" name="check_in" value="<?= e($selection['check_in']) ?>" min="<?= e(gmdate('Y-m-d')) ?>" required>
      </div>
      <div class="si-field">
        <label for="booking-out">Check out</label>
        <input class="si-input" id="booking-out" type="date" name="check_out" value="<?= e($selection['check_out']) ?>" min="<?= e(gmdate('Y-m-d', strtotime('+1 day'))) ?>" required>
      </div>
    </div>
    <div class="si-field-row si-field-row--2">
      <div class="si-field">
        <label for="booking-guests">Guests</label>
        <input class="si-input" id="booking-guests" type="number" name="guests" min="1" max="50" value="<?= e($selection['guests']) ?>" required inputmode="numeric">
      </div>
      <div class="si-field">
        <label for="booking-rooms">Rooms</label>
        <input class="si-input" id="booking-rooms" type="number" name="rooms" min="1" max="20" value="<?= e($selection['rooms']) ?>" required inputmode="numeric">
      </div>
    </div>
    <button class="si-btn <?= $quote ? 'si-btn--outline' : 'si-btn--primary' ?> si-btn--block" type="submit"><?= $quote ? 'Update availability' : 'Check availability' ?></button>
  </form>
  <?php else: ?>
  <p class="si-caption">No rooms are currently open for reservation at this property.</p>
  <?php endif; ?>

  <?php if ($quote): ?>
  <div class="si-quote" aria-label="Price for the checked dates">
    <p class="si-quote__row" style="color:var(--si-text-muted)"><?= e($quote['check_in']) ?> — <?= e($quote['check_out']) ?> · <?= (int) $quote['guests'] ?> guests</p>
    <?php foreach (['subtotal_minor' => $quote['nights'] . ' nights · ' . $quote['rooms'] . ' room(s)', 'fees_minor' => 'Fees', 'tax_minor' => 'Taxes', 'total_minor' => 'Total'] as $key => $label): ?>
    <div class="si-quote__row<?= $key === 'total_minor' ? ' si-quote__row--total' : '' ?>">
      <span><?= e($label) ?></span>
      <strong><?= e(format_money(\App\Services\Money::decimal($quote[$key]), (string) $quote['currency'])) ?></strong>
    </div>
    <?php endforeach; ?>
    <p class="si-hint">All amounts in <?= e($quote['currency']) ?>. Availability is checked again when you reserve.</p>
    <form action="<?= e(url('/bookings/hold')) ?>" method="POST" style="margin-block-start:var(--si-space-3)"<?= track('booking_started', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>>
      <?= csrf_field() ?>
      <?php foreach (['room_id', 'check_in', 'check_out', 'guests', 'rooms'] as $key): ?>
      <input type="hidden" name="<?= e($key) ?>" value="<?= e($quote[$key]) ?>">
      <?php endforeach; ?>
      <button class="si-btn si-btn--primary si-btn--block si-btn--lg" type="submit"><?= empty($authUser) ? 'Sign in to reserve' : 'Reserve this stay' ?></button>
    </form>
  </div>
  <?php endif; ?>

  <hr class="si-divider">

  <div class="si-stack si-stack--sm">
    <?php if (auth_check() && $propertyId > 0): ?>
    <form method="POST" action="<?= e(url('/guest/favourites')) ?>"<?= track('favourite_toggled', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>>
      <?= csrf_field() ?>
      <input type="hidden" name="property_id" value="<?= e($propertyId) ?>">
      <input type="hidden" name="action" value="<?= $saved ? 'remove' : 'add' ?>">
      <button type="submit" class="si-btn si-btn--outline si-btn--block" data-favourite-toggle aria-pressed="<?= $saved ? 'true' : 'false' ?>"><?= icon($saved ? 'heart-filled' : 'heart') ?> <?= $saved ? 'Saved' : 'Save this stay' ?></button>
    </form>
    <?php else: ?>
    <a class="si-btn si-btn--outline si-btn--block" href="<?= e(url('/login')) ?>"><?= icon('heart') ?> Sign in to save</a>
    <?php endif; ?>

    <button type="button" class="si-btn si-btn--outline si-btn--block" data-dialog-open="shareDialog"<?= track('listing_share', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>><?= icon('share') ?> Share this stay</button>
    <a class="si-btn si-btn--outline si-btn--block" href="<?= e(url('/contact?' . http_build_query(['property' => $property['slug']]))) ?>"<?= track('agent_contact', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>><?= icon('send') ?> Send inquiry</a>
    <?php if ($whatsappEnabled): ?>
    <a class="si-btn si-btn--outline si-btn--block" target="_blank" rel="noopener noreferrer"
       href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $whatsappNumber)) ?>?text=<?= e(rawurlencode('Hello StayIn, I would like to ask about ' . $property['name'] . '.')) ?>"
       <?= track('agent_whatsapp_click', ['entity_id' => $propertyId, 'surface' => 'booking_panel']) ?>><?= icon('whatsapp') ?> Message on WhatsApp</a>
    <?php endif; ?>
  </div>
</div>

