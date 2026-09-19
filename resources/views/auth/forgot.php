<?php
/** @var bool $errors */
/** @var string $csrfToken */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);">
      <h1 class="auth__title"><?= __('auth.reset_title') ?></h1>
      <p class="text-muted"><?= __('auth.reset_subtitle') ?></p>
      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert"><?= e($errors[0] ?? 'An error occurred') ?></div>
      <?php endif; ?>
      <form method="POST" action="/password/forgot" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email"
            value="<?= e(old('email')) ?>" placeholder="you@example.com">
        </div>
        <button type="submit" class="btn btn--primary btn--block">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <?= __('auth.send_link') ?>
        </button>
      </form>
      <p style="margin-top:var(--space-4);text-align:center;">
        <a href="/login"><?= __('auth.submit') ?> back to login</a>
      </p>
    </div>
  </div>
</section>
<?php View::stop('content') ?>
