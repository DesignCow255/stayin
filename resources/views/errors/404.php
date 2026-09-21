<?php
/** @var int $status */
/** @var string $message */
?>
<div class="error-page" style="text-align:center;padding-block:var(--space-8) 0">
  <div style="font-size:clamp(4rem,12vw,6rem);line-height:1.1;color:var(--text-faint);margin-block-end:var(--space-4)">
    <i class="fa-solid fa-magnifying-glass-question" aria-hidden="true"></i>
  </div>
  <h1 style="font-size:clamp(1.5rem,4vw,2.2rem);margin:0 0 var(--space-3)">404 — Page not found</h1>
  <p class="text-muted" style="max-width:var(--container-narrow);margin-inline:auto;font-size:var(--text-lg)">The page you're looking for doesn't exist. It may have moved or been removed.</p>
  <a href="<?= e(url('/')) ?>" class="btn btn--primary btn--lg" style="margin-top:var(--space-5)"><i class="fa-solid fa-house" aria-hidden="true"></i> Return home</a>
</div>