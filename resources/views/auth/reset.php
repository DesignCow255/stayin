<?php
/** @var bool $errors */
/** @var string $csrfToken */
/** @var string $token */
use App\Core\View;
\App\Core\View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);">
      <h1 class="auth__title">Set new password</h1>
      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert"><?= e(implode(' ', (array) $errors)) ?></div>
      <?php endif; ?>
      <form method="POST" action="<?= e(url('/reset-password')) ?>" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email"
            value="<?= e(old('email')) ?>" placeholder="you@example.com">
        </div>
        <div class="form-group">
          <label for="password">New password</label>
          <input id="password" name="password" type="password" required minlength="8"
            autocomplete="new-password" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirm new password</label>
          <input id="password_confirmation" name="password_confirmation" type="password"
            required autocomplete="new-password" placeholder="••••••••">
        </div>
        <button type="submit" class="btn btn--primary btn--block">
          <i class="fa-solid fa-key" aria-hidden="true"></i> Reset password
        </button>
      </form>
    </div>
  </div>
</section>
<?php \App\Core\View::stop() ?>
