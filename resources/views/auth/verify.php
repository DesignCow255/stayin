<?php
/** @var bool $errors */
/** @var string $csrfToken */
/** @var string|null $email */
use App\Core\View;
$email = isset($email) && is_string($email) && trim($email) !== '' ? trim($email) : null;
$csrfToken = isset($csrfToken) && is_string($csrfToken) && $csrfToken !== '' ? $csrfToken : csrf_token();
View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);text-align:center;">
      <div style="font-size:3rem;color:var(--primary);margin-bottom:var(--space-4);">
        <i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
      </div>
      <h1 class="auth__title">Verify your email</h1>
      <p class="text-muted">
        <?php if ($email !== null): ?>
          We've sent a verification link to <?= e($email) ?>.
        <?php else: ?>
          Use the verification link sent to your email address.
        <?php endif; ?>
      </p>
      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert"><?= e(implode(' ', (array) $errors)) ?></div>
      <?php endif; ?>
      <p class="text-muted">For your security, StayIn verifies accounts through single-use email links instead of browser-entered codes.</p>
      <p style="margin-top:var(--space-4);">
        <form method="POST" action="<?= e(url('/verify-email/resend')) ?>" style="display:inline;">
          <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
          <button type="submit" class="btn btn--primary btn--block">Resend verification link</button>
        </form>
      </p>
      <p style="margin-top:var(--space-2);">
        <a href="<?= e(url('/login')) ?>">Back to sign in</a>
      </p>
    </div>
  </div>
</section>
<?php View::stop() ?>
