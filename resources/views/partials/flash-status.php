<?php
/** @var array{type: string, message: string}|null $flash */
$flash = $flashStatus ?? flash_status();
if (!$flash) {
    return;
}
$type = $flash['type'] ?? 'info';
$icon = match ($type) {
    'success' => 'fa-check-circle',
    'error', 'danger' => 'fa-x-circle',
    'warning' => 'fa-triangle-exclamation',
    default => 'fa-circle-info',
};
?>
<div class="alert alert--<?= e($type === 'error' || $type === 'danger' ? 'error' : ($type === 'success' ? 'success' : ($type === 'warning' ? 'warning' : 'info'))) ?>" id="flash-status" role="status" aria-live="polite">
  <i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i>
  <span><?= e($flash['message']) ?></span>
  <button class="alert__close" onclick="this.parentElement.remove()" aria-label="Dismiss"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
</div>