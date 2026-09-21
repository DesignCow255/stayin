<?php
/** @var array{type: string, message: string}|null $flash */
$flash = $flashStatus ?? flash_status();
if (!$flash) {
    return;
}
$type = $flash['type'] ?? 'info';
$variant = $type === 'error' || $type === 'danger' ? 'error' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info'));
$icon = match ($variant) {
    'success' => 'check-circle',
    'error' => 'error',
    'warning' => 'alert',
    default => 'info',
};
?>
<div class="si-alert si-alert--<?= e($variant) ?>" id="flash-status" role="status" aria-live="polite" data-flash-dismiss>
  <?= icon($icon) ?>
  <span><?= e($flash['message']) ?></span>
  <button type="button" class="si-alert__close" data-dismiss-flash aria-label="Dismiss"><?= icon('close', 'si-icon--sm') ?></button>
</div>