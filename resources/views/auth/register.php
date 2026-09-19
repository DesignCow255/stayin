<?php
/** @var bool $errors */
/** @var string $csrfToken */
use App\Core\View;
View::start('content');
?>
<section class="page-section">
  <div class="container" style="max-width:var(--container-sm);">
    <div class="card" style="padding:var(--space-6);">
      <h1 class="auth__title"><?= __('auth.register_title') ?></h1>
      <p class="text-muted"><?= __('auth.register_subtitle') ?></p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error" role="alert" data-flash-dismiss>
          <ul style="margin:0;padding-left:var(--space-3);">
            <?php foreach (is_array($errors) ? $errors : [$errors] as $error): ?>
              <li><?= e($error) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="/register" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-group">
          <label for="name"><?= __('auth.name') ?></label>
          <input id="name" name="name" type="text" required minlength="2"
            value="<?= e(old('name')) ?>" placeholder="Jane Doe" aria-describedby="name-error">
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email"
            value="<?= e(old('email')) ?>" placeholder="you@example.com" aria-describedby="email-error">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="8"
            autocomplete="new-password" placeholder="••••••••" aria-describedby="password-error">
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirm password</label>
          <input id="password_confirmation" name="password_confirmation" type="password"
            required autocomplete="new-password" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label for="role" style="display:flex;align-items:center;gap:var(--space-1);">
            <select id="role" name="role">
              <option value="guest">I'm a guest</option>
              <option value="host">I'm a host / property operator</option>
            </select>
          </label>
        </div>
        <button type="submit" class="btn btn--primary btn--block">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create account
        </button>
      </form>

      <p style="margin-top:var(--space-4);text-align:center;">
        <?= __('auth.or') ?> <a href="/login"><?= __('auth.login') ?></a>
      </p>
    </div>
  </div>
</section>
<?php View::stop('content') ?>
