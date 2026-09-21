<?php
/** @var bool|array $errors */
/** @var string $csrfToken */
use App\Core\View;
View::start('content');
$selectedRole = old('role', 'guest');
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

      <form method="POST" action="<?= e(url('/register')) ?>" class="auth-form" data-validate>
        <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
        <div class="form-row">
          <div class="form-group">
            <label for="first_name">First name</label>
            <input id="first_name" name="first_name" type="text" required minlength="2" maxlength="100"
              autocomplete="given-name" value="<?= e(old('first_name')) ?>" placeholder="Jane" aria-describedby="first-name-error">
          </div>
          <div class="form-group">
            <label for="last_name">Last name</label>
            <input id="last_name" name="last_name" type="text" required minlength="2" maxlength="100"
              autocomplete="family-name" value="<?= e(old('last_name')) ?>" placeholder="Doe" aria-describedby="last-name-error">
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input id="email" name="email" type="email" required autocomplete="email"
            value="<?= e(old('email')) ?>" placeholder="you@example.com" aria-describedby="email-error">
        </div>
        <div class="form-group">
          <label for="phone">Phone <span class="text-muted">optional</span></label>
          <input id="phone" name="phone" type="tel" autocomplete="tel"
            value="<?= e(old('phone')) ?>" placeholder="+255 700 000 000" aria-describedby="phone-error">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" required minlength="10" maxlength="72"
            autocomplete="new-password" placeholder="At least 10 characters" aria-describedby="password-error password-help">
          <p id="password-help" class="text-muted" style="margin-top:var(--space-1);">Use 10–72 characters. Passwords are stored securely and never shown to staff.</p>
        </div>
        <div class="form-group">
          <label for="password_confirmation">Confirm password</label>
          <input id="password_confirmation" name="password_confirmation" type="password"
            required autocomplete="new-password" placeholder="••••••••">
        </div>
        <div class="form-group">
          <label for="role">Account type</label>
          <select id="role" name="role" required>
            <option value="guest" <?= $selectedRole === 'guest' ? 'selected' : '' ?>>I'm a guest</option>
            <option value="host" <?= $selectedRole === 'host' ? 'selected' : '' ?>>I'm a host / property operator</option>
          </select>
          <p class="text-muted" style="margin-top:var(--space-1);">Admin accounts are not created here and require the separate secure admin portal.</p>
        </div>
        <button type="submit" class="btn btn--primary btn--block">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Create account
        </button>
      </form>

      <p style="margin-top:var(--space-4);text-align:center;">
        <?= __('auth.or') ?> <a href="<?= e(url('/login')) ?>"><?= __('auth.login') ?></a>
      </p>
    </div>
  </div>
</section>
<?php View::stop('content') ?>
