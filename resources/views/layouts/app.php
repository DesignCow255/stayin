<?php
use App\Core\Session;
use App\Core\View;

/** @var array $authUser */
/** @var array $authRoles */
/** @var string $csrfToken */
/** @var string $currentPath */
/** @var string $appName */
/** @var string $appUrl */
?>
<!DOCTYPE html>
<html lang="<?= e(app_locale()) ?>" data-theme="<?= e(Session::get('theme', 'system')) ?>">
<head>
<?= View::fragment('partials/seo-head', compact('metaTitle', 'metaDescription', 'metaImage', 'canonical', 'robots')) ?>
<link rel="stylesheet" href="<?= e(asset('assets/vendor/fontawesome/css/all.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/pages.css')) ?>">
<style>
  .sr-only, .skip-link { display: none; }
</style>
</head>
<body<?= $authUser ? ' data-user="1"' : '' ?>>
<a href="#main" class="skip-link">Skip to content</a>

<header class="site-header">
  <div class="container site-header__inner">
    <a href="/" class="brand" aria-label="<?= e($appName) ?> home">
      <img src="<?= e(asset('assets/images/logo.svg')) ?>" alt="<?= e($appName) ?>" width="34" height="34" onerror="this.src='<?= e(asset('assets/images/favicon.svg')) ?>'">
      <span><?= e($appName) ?></span>
    </a>
    <button class="nav__toggle" type="button" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
      <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
    <nav class="nav" id="siteNav" aria-label="Primary">
      <a href="/" class="nav__link<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>">Home</a>
      <a href="/stays" class="nav__link<?= str_starts_with($currentPath, '/stays') && $currentPath !== '/stays' ? ' aria-current="page"' : '' ?>">Stays</a>
      <a href="/search" class="nav__link<?= $currentPath === '/search' ? ' aria-current="page"' : '' ?>">Search</a>
      <a href="/about" class="nav__link">About</a>
      <a href="/contact" class="nav__link">Contact</a>
      <?php if ($authUser): ?>
        <?php if (in_array('host', $authRoles, true)): ?>
        <a href="/host" class="nav__link<?= str_starts_with($currentPath, '/host') ? ' aria-current="page"' : '' ?>">Host</a>
        <?php endif; ?>
        <?php if (in_array('admin', $authRoles, true)): ?>
        <a href="/admin" class="nav__link<?= str_starts_with($currentPath, '/admin') ? ' aria-current="page"' : '' ?>">Admin</a>
        <?php endif; ?>
        <a href="/guest" class="nav__link">My StayIn</a>
        <form method="POST" action="/logout" class="nav__logout" aria-label="Sign out">
          <?= csrf_field() ?>
          <button type="submit" class="nav__link nav__logout-btn"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i> Sign out</button>
        </form>
      <?php else: ?>
        <a href="/login" class="nav__link">Sign in</a>
        <a href="/register" class="nav__link nav__cta">Get started</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<main id="main" class="site-main">
  <?= View::fragment('partials/flash-status', ['flashStatus' => $flashStatus]) ?>
  <?= View::section('content') ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div>
        <h3><?= e($appName) ?></h3>
        <p>Book distinctive stays across Tanzania. Simple pricing in Tshs or USD, verified hosts, and secure mobile money payments.</p>
      </div>
      <div>
        <h3>Explore</h3>
        <ul>
          <li><a href="/stays">Browse stays</a></li>
          <li><a href="/search">Search</a></li>
          <li><a href="/about">About</a></li>
          <li><a href="/help">Help centre</a></li>
        </ul>
      </div>
      <div>
        <h3>For hosts</h3>
        <ul>
          <li><a href="/register?role=host">Become a host</a></li>
          <li><a href="/host">Host workspace</a></li>
          <li><a href="/contact">Support</a></li>
        </ul>
      </div>
      <div>
        <h3>Company</h3>
        <ul>
          <li><a href="/about">About StayIn</a></li>
          <li><a href="/privacy">Privacy policy</a></li>
          <li><a href="/terms">Terms &amp; conditions</a></li>
          <li><a href="/contact">Contact</a></li>
        </ul>
      </div>
    </div>
    <div class="site-footer__bottom">
      <span>&copy; <?= date('Y') ?> <?= e($appName) ?>. All rights reserved.</span>
      <span>
        <?= e($appName) ?> · <?= e(config('seo.organisation.address', 'Dar es Salaam, Tanzania')) ?>
      </span>
      <span>
        <?php if (!empty($csrfToken)): ?>
          <span class="csrf-token" data-csrf="<?= e($csrfToken) ?>" aria-hidden="true"></span>
        <?php endif; ?>
        <span class="theme-toggle" id="themeToggle" role="button" tabindex="0" aria-label="Toggle colour theme"><i class="fa-solid fa-moon" aria-hidden="true"></i></span>
      </span>
    </div>
  </div>
</footer>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer="defer"></script>

</body>
</html>