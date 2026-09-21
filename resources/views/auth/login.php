<?php
/** @var bool $errors */
/** @var string $csrfToken */
use App\Core\View;
\App\Core\View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);">
      <h1 class="auth__title"><?= __('auth.login_title') ?></h1>
      <p class="text-muted"><?= __('auth.login_subtitle') ?></p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert" data-flash-dismiss>
          <ul style="margin:0;padding-left:var(--space-3);">
            <?php foreach (is_array($errors) ? $errors : [$errors] as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= e(url('/login')) ?>" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-group">
          <label for="email"><?= __('auth.email') ?></label>
          <input id="email" name="email" type="email" required autocomplete="email"
            value="<?= e(old('email')) ?>" placeholder="you@example.com"
            aria-describedby="email-error">
        </div>
        <div class="form-group">
          <label for="password"><?= __('auth.password') ?></label>
          <input id="password" name="password" type="password" required minlength="8" autocomplete="current-password"
            placeholder="••••••••" aria-describedby="password-error">
        </div>
        <div class="form-group" style="display:flex;align-items:center;gap:var(--space-2);">
          <input id="remember" name="remember" type="checkbox" value="1" id="remember">
          <label for="remember" style="margin-bottom:0;"><?= __('auth.remember') ?></label>
        </div>
        <button type="submit" class="btn btn--primary btn--block">
          <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> <?= __('auth.submit') ?>
        </button>
      </form>

      <div class="auth__links">
        <a href="<?= e(url('/forgot-password')) ?>"><?= __('auth.forgot') ?></a>
        <p style="margin-top:var(--space-3);"><?= __('auth.no_account') ?>
          <a href="<?= e(url('/register')) ?>" class="btn btn--ghost btn--sm"><?= __('auth.register') ?></a>
        </p>
      </div>
    </div>
  </div>
</section>
<?php \App\Core\View::stop('content') ?>
