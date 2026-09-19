<?php
/** @var bool $errors */
/** @var string $csrfToken */
/** @var string|null $email */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);text-align:center;">
      <div style="font-size:3rem;color:var(--primary);margin-bottom:var(--space-4);">
        <i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i>
      </div>
      <h1 class="auth__title">Verify your email</h1>
      <p class="text-muted">We've sent a 6-digit code to your email.</p>
      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert"><?= e(implode(' ', (array) $errors)) ?></div>
      <?php endif; ?>
      <form method="POST" action="/verify" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <?php if ($email): ?>
          <input type="hidden" name="email" value="<?= e($email) ?>">
        <?php endif; ?>
        <div class="form-group">
          <label for="code">Verification code</label>
          <input id="code" name="code" type="text" inputmode="numeric" required
            autocomplete="one-time-code" placeholder="000000"
            style="font-size:1.5rem;text-align:center;letter-spacing:0.5rem;"
            pattern="\d{6}" maxlength="6">
        </div>
        <button type="submit" class="btn btn--primary btn--block">Verify</button>
      </form>
      <p style="margin-top:var(--space-4);">
        <form method="POST" action="/verify/resend" style="display:inline;">
          <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
          Resend code
        </form>
      </p>
      <p style="margin-top:var(--space-2);">
        <a href="/login">Back to sign in</a>
      </p>
    </div>
  </div>
</section>
<?php View::stop() ?>
