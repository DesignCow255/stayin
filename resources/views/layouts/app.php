<?php

use App\Core\Session;

// -----------------------------------------------------------------------------
// StayIn global layout context
// -----------------------------------------------------------------------------

$title = $title ?? 'StayIn';

$appName = $appName ?? config('app.name', 'StayIn');
$appUrl = $appUrl ?? config('app.url', 'http://localhost/stayin');

$metaTitle = $metaTitle ?? $title;
$metaDescription = $metaDescription ?? '';
$metaImage = $metaImage ?? '';
$canonical = $canonical ?? '';
$robots = $robots ?? 'index,follow';

$authUser = $authUser ?? null;
$authRoles = $authRoles ?? [];
$isAuthenticated = $authUser !== null;
$isHost = in_array('host', $authRoles, true);
$isAdmin = count(array_intersect(['admin', 'super_admin', 'support', 'finance'], $authRoles)) > 0;
$roleHome = $isAdmin ? '/admin' : ($isHost ? '/host' : '/guest');
$roleLabel = $isAdmin ? 'Admin profile' : ($isHost ? 'Host profile' : 'Guest profile');

$csrfToken = $csrfToken ?? '';
$flashStatus = $flashStatus ?? null;

$currentPath = $currentPath
    ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
    ?? '/';

$bodyClass = $bodyClass ?? '';

// Unread in-app notification count for the authenticated badge. Fail-soft:
// the header must never break because of a counter query.
$unreadCount = 0;
$savedCount = 0;
if ($isAuthenticated) {
    try {
        $viewerId = (int) ($authUser['id'] ?? 0);
        if ($viewerId > 0) {
            $unreadCount = (int) (\App\Core\Database::scalar(
                "SELECT COUNT(*) FROM `notifications` WHERE `user_id` = ? AND `is_read` = 0",
                [$viewerId]
            ));
            $savedCount = (int) (\App\Core\Database::scalar(
                "SELECT COUNT(*) FROM `favourites` f JOIN `properties` p ON p.`id` = f.`property_id` WHERE f.`user_id` = ? AND p.`status` = 'published'",
                [$viewerId]
            ));
        }
    } catch (\Throwable $e) {
        $unreadCount = 0;
        $savedCount = 0;
    }
}

$viewerInitials = '';
if ($isAuthenticated) {
    $first = trim((string) ($authUser['first_name'] ?? ''));
    $last = trim((string) ($authUser['last_name'] ?? ''));
    $viewerInitials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last !== '' ? $last : $first, 0, 1));
    if ($viewerInitials === '') {
        $viewerInitials = '•';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= e(app_locale()) ?>" data-theme="<?= e(Session::get('theme', 'light')) ?>">
<head>
<?= \App\Core\View::fragment('partials/seo-head', compact('metaTitle', 'metaDescription', 'metaImage', 'canonical', 'robots')) ?>
</head>
<body<?= $authUser ? ' data-user="1"' : '' ?><?= $isAuthenticated ? ' class="has-tabbar"' : '' ?>>
<a href="#main" class="skip-link">Skip to content</a>

<header class="si-header" id="siteHeader" data-elevated="false">
  <div class="si-container si-header__inner">
    <a href="<?= e(url('/')) ?>" class="si-brand" aria-label="<?= e($appName) ?> home">
      <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="<?= e($appName) ?>" width="118" height="44">
    </a>
    <nav class="si-nav" aria-label="Primary">
      <a href="<?= e(url('/')) ?>" class="si-nav__link"<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>>Home</a>
      <a href="<?= e(url('/stays')) ?>" class="si-nav__link"<?= str_starts_with($currentPath, '/stays') || str_starts_with($currentPath, '/property') ? ' aria-current="page"' : '' ?>>Stays</a>
      <a href="<?= e(url('/search')) ?>" class="si-nav__link"<?= $currentPath === '/search' ? ' aria-current="page"' : '' ?>>Search</a>
      <a href="<?= e(url('/about')) ?>" class="si-nav__link"<?= $currentPath === '/about' ? ' aria-current="page"' : '' ?>>About</a>
      <a href="<?= e(url('/contact')) ?>" class="si-nav__link"<?= $currentPath === '/contact' ? ' aria-current="page"' : '' ?>>Contact</a>
      <?php if ($isHost): ?>
      <a href="<?= e(url('/host')) ?>" class="si-nav__link"<?= str_starts_with($currentPath, '/host') ? ' aria-current="page"' : '' ?>>Host</a>
      <?php endif; ?>
      <?php if ($isAdmin): ?>
      <a href="<?= e(url('/admin')) ?>" class="si-nav__link"<?= str_starts_with($currentPath, '/admin') ? ' aria-current="page"' : '' ?>>Admin</a>
      <?php endif; ?>
    </nav>
    <div class="si-header__actions">
      <a href="<?= e(url('/search')) ?>" class="si-icon-btn" aria-label="Search stays"><?= icon('search', 'si-icon--lg') ?></a>
      <?php if ($isAuthenticated): ?>
      <?php if (!$isAdmin && !$isHost): ?>
      <a href="<?= e(url('/guest/favourites')) ?>" class="si-icon-btn" aria-label="Saved stays<?= $savedCount > 0 ? ', ' . $savedCount . ' saved' : '' ?>"><?= icon('heart', 'si-icon--lg') ?><?php if ($savedCount > 0): ?><span class="si-icon-btn__count" aria-hidden="true"><?= e($savedCount > 99 ? '99+' : (string) $savedCount) ?></span><?php endif; ?></a>
      <a href="<?= e(url('/guest#notifications')) ?>" class="si-icon-btn" aria-label="Notifications<?= $unreadCount > 0 ? ', ' . $unreadCount . ' unread' : '' ?>"><?= icon('bell', 'si-icon--lg') ?><?php if ($unreadCount > 0): ?><span class="si-icon-btn__count" aria-hidden="true"><?= e($unreadCount > 99 ? '99+' : (string) $unreadCount) ?></span><?php endif; ?></a>
      <?php endif; ?>
      <a href="<?= e(url($roleHome)) ?>" class="si-icon-btn" aria-label="<?= e($roleLabel) ?>"><span class="si-avatar" aria-hidden="true"><?= e($viewerInitials) ?></span></a>
      <form method="POST" action="<?= e(url('/logout')) ?>" class="si-header__cta" aria-label="Sign out" style="display:inline-flex">
        <?= csrf_field() ?>
        <button type="submit" class="si-btn si-btn--outline si-btn--sm"><?= icon('log-out', 'si-icon--sm') ?>Sign out</button>
      </form>
      <?php endif; ?>
      <?php if ($isHost): ?>
      <a href="<?= e(url('/host/properties/create')) ?>" class="si-btn si-btn--primary si-btn--sm si-header__cta"<?= track('listing_started', ['surface' => 'header']) ?>>List your property</a>
      <?php elseif (!$isAuthenticated): ?>
      <a href="<?= e(url('/register')) ?>" class="si-btn si-btn--primary si-btn--sm si-header__cta">List your property</a>
      <?php endif; ?>
      <?php if (!$isAuthenticated): ?>
      <a href="<?= e(url('/login')) ?>" class="si-btn si-btn--outline si-btn--sm si-header__cta">Sign in</a>
      <?php endif; ?>
      <button class="si-icon-btn si-header__toggle" type="button" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileDrawer"><?= icon('menu', 'si-icon--lg') ?></button>
    </div>
  </div>
</header>

<div class="si-drawer" id="mobileDrawer" role="dialog" aria-modal="true" aria-label="Menu" hidden>
  <div class="si-drawer__head">
    <a href="<?= e(url('/')) ?>" class="si-brand" aria-label="<?= e($appName) ?> home">
      <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="<?= e($appName) ?>" width="118" height="44">
    </a>
    <button class="si-icon-btn" type="button" id="drawerClose" aria-label="Close menu" style="margin-inline-start:auto"><?= icon('close', 'si-icon--lg') ?></button>
  </div>
  <div class="si-drawer__body">
    <div class="si-drawer__group">
      <p class="si-drawer__label">Discover</p>
      <a href="<?= e(url('/')) ?>" class="si-drawer__link"<?= $currentPath === '/' ? ' aria-current="page"' : '' ?>><span>Home</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/stays')) ?>" class="si-drawer__link"<?= str_starts_with($currentPath, '/stays') || str_starts_with($currentPath, '/property') ? ' aria-current="page"' : '' ?>><span>Stays</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/search')) ?>" class="si-drawer__link"<?= $currentPath === '/search' ? ' aria-current="page"' : '' ?>><span>Search</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/about')) ?>" class="si-drawer__link"><span>About</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/contact')) ?>" class="si-drawer__link"><span>Contact</span><?= icon('arrow-right') ?></a>
    </div>
    <div class="si-drawer__group">
      <p class="si-drawer__label">Your StayIn</p>
      <?php if ($isAuthenticated): ?>
      <a href="<?= e(url($roleHome)) ?>" class="si-drawer__link"><span><?= e($roleLabel) ?></span><?= icon('arrow-right') ?></a>
      <?php if (!$isAdmin && !$isHost): ?>
      <a href="<?= e(url('/guest/favourites')) ?>" class="si-drawer__link"><span>Saved<?= $savedCount > 0 ? ' (' . $savedCount . ')' : '' ?></span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/guest#notifications')) ?>" class="si-drawer__link"><span>Alerts<?= $unreadCount > 0 ? ' (' . $unreadCount . ')' : '' ?></span><?= icon('arrow-right') ?></a>
      <?php elseif ($isHost): ?>
      <a href="<?= e(url('/host/properties')) ?>" class="si-drawer__link"><span>My properties</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/host/bookings')) ?>" class="si-drawer__link"><span>Host bookings</span><?= icon('arrow-right') ?></a>
      <?php elseif ($isAdmin): ?>
      <a href="<?= e(url('/admin/content')) ?>" class="si-drawer__link"><span>Content studio</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/exports/bookings')) ?>" class="si-drawer__link"><span>Reports</span><?= icon('arrow-right') ?></a>
      <?php endif; ?>
      <?php else: ?>
      <a href="<?= e(url('/login')) ?>" class="si-drawer__link"><span>Sign in</span><?= icon('arrow-right') ?></a>
      <a href="<?= e(url('/register')) ?>" class="si-drawer__link"><span>Create account</span><?= icon('arrow-right') ?></a>
      <?php endif; ?>
    </div>
    <div class="si-drawer__group">
      <?php if ($isHost): ?>
      <a href="<?= e(url('/host/properties/create')) ?>" class="si-btn si-btn--primary si-btn--block">List your property</a>
      <?php elseif (!$isAuthenticated): ?>
      <a href="<?= e(url('/register')) ?>" class="si-btn si-btn--primary si-btn--block">List your property</a>
      <?php endif; ?>
      <?php if ($isAuthenticated): ?>
      <form method="POST" action="<?= e(url('/logout')) ?>" aria-label="Sign out">
        <?= csrf_field() ?>
        <button type="submit" class="si-btn si-btn--outline si-btn--block"><?= icon('log-out') ?> Sign out</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($isAuthenticated): ?>
<nav class="si-tabbar" aria-label="Your StayIn">
  <a href="<?= e(url($roleHome)) ?>" class="si-tabbar__item"<?= str_starts_with($currentPath, $roleHome) ? ' aria-current="page"' : '' ?>><?= icon('house') ?><span>Dashboard</span></a>
  <?php if ($isAdmin): ?>
  <a href="<?= e(url('/admin/content')) ?>" class="si-tabbar__item"><?= icon('images') ?><span>Content</span></a>
  <a href="<?= e(url('/exports/bookings')) ?>" class="si-tabbar__item"><?= icon('chart') ?><span>Reports</span></a>
  <?php elseif ($isHost): ?>
  <a href="<?= e(url('/host/properties')) ?>" class="si-tabbar__item"><?= icon('building') ?><span>Listings</span></a>
  <a href="<?= e(url('/host/bookings')) ?>" class="si-tabbar__item"><?= icon('calendar') ?><span>Bookings</span></a>
  <?php else: ?>
  <a href="<?= e(url('/search')) ?>" class="si-tabbar__item"<?= $currentPath === '/search' ? ' aria-current="page"' : '' ?>><?= icon('search') ?><span>Explore</span></a>
  <a href="<?= e(url('/guest/favourites')) ?>" class="si-tabbar__item"<?= str_starts_with($currentPath, '/guest/favourites') ? ' aria-current="page"' : '' ?>><?= icon('heart') ?><span>Saved</span><?php if ($savedCount > 0): ?><span class="si-tabbar__count" aria-hidden="true"><?= e($savedCount > 99 ? '99+' : (string) $savedCount) ?></span><?php endif; ?></a>
  <?php endif; ?>
  <form method="POST" action="<?= e(url('/logout')) ?>" class="si-tabbar__item" aria-label="Sign out">
    <?= csrf_field() ?>
    <button type="submit" style="all:unset;display:grid;place-items:center;gap:.125rem;cursor:pointer"><?= icon('log-out') ?><span>Sign out</span></button>
  </form>
</nav>
<?php endif; ?>

<main id="main" class="site-main">
  <?= \App\Core\View::fragment('partials/flash-status', ['flashStatus' => $flashStatus]) ?>
  <?= \App\Core\View::section('content') ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__grid">
      <div>
        <a href="<?= e(url('/')) ?>" class="si-brand" aria-label="<?= e($appName) ?> home">
          <img src="<?= e(asset('assets/images/logo.png')) ?>" alt="<?= e($appName) ?>" width="161" height="60">
        </a>
        <h3><?= e($appName) ?></h3>
        <p>Distinctive places to stay across Tanzania. Find the surroundings that suit you.</p>
      </div>
      <div>
        <h3>Explore</h3>
        <ul>
          <li><a href="<?= e(url('/stays')) ?>">Browse stays</a></li>
          <li><a href="<?= e(url('/search')) ?>">Search</a></li>
          <li><a href="<?= e(url('/about')) ?>">About</a></li>
          <li><a href="<?= e(url('/help')) ?>">Help centre</a></li>
        </ul>
      </div>
      <div>
        <h3>For hosts</h3>
        <ul>
          <li><a href="<?= e(url('/register?role=host')) ?>">Become a host</a></li>
          <li><a href="<?= e(url('/host')) ?>">Host workspace</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Support</a></li>
        </ul>
      </div>
      <div>
        <h3>Company</h3>
        <ul>
          <li><a href="<?= e(url('/about')) ?>">About StayIn</a></li>
          <li><a href="<?= e(url('/privacy')) ?>">Privacy policy</a></li>
          <li><a href="<?= e(url('/terms')) ?>">Terms &amp; conditions</a></li>
          <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
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
        <button type="button" class="si-theme-toggle" id="themeToggle" aria-label="Toggle colour theme"><?= icon('moon') ?></button>
      </span>
    </div>
  </div>
</footer>

<?php
// WhatsApp floating chat button — uses the configured support number (config/support.php).
$whatsappNumber = (string) (config('support.support_whatsapp') ?? '');
if ($whatsappNumber !== ''):
    $waText = 'Hi StayIn — I have a question about a stay.';
    $waHref = 'https://wa.me/' . rawurlencode($whatsappNumber) . '?text=' . rawurlencode($waText);
?>
<a href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer"
   class="si-whatsapp-float" aria-label="Chat on WhatsApp"
   data-analytics="surface.whatsapp.click"
   data-analytics-surface="footer"><?= icon('whatsapp', 'si-icon--lg') ?></a>
<?php endif; ?>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer="defer"></script>

</body>
</html>