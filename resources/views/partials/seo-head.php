<?php
/** @var string $metaTitle */
/** @var string $metaDescription */
/** @var string|null $metaImage */
/** @var string|null $canonical */
$title = $metaTitle ?? config('seo.default_title', 'StayIn');
$description = $metaDescription ?? config('seo.default_description', '');
$image = $metaImage ?? config('seo.default_image', 'assets/images/og-default.svg');
$siteName = config('seo.site_name', 'StayIn');
$separator = config('seo.title_separator', ' · ');
$locale = app_locale();
$url = current_request()?->url() ?? config('app.url');
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="language" content="<?= e($locale) ?>">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($description) ?>">
<meta name="robots" content="<?= e($robots ?? 'index, follow') ?>">
<link rel="canonical" href="<?= e($canonical ?? $url) ?>">
<?php if (!empty($image)): ?>
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:image" content="<?= e(image_url((string) $image, 'assets/images/og-default.svg')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="<?= e($locale) ?>">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($title) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e(image_url((string) $image, 'assets/images/og-default.svg')) ?>">
<?php endif; ?>
<meta name="theme-color" content="#F6F3EE">
<?php foreach (['assets/css/tokens.css', 'assets/css/base.css', 'assets/css/layout.css', 'assets/css/components.css', 'assets/css/pages.css', 'assets/css/system.css', 'assets/css/dashboard.css', 'assets/css/utilities.css', 'assets/css/responsive.css'] as $css): ?>
<link rel="stylesheet" href="<?= e(asset($css)) ?>" media="screen">
<?php endforeach; ?>
<link rel="icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" type="image/svg+xml" sizes="any">
<link rel="icon" href="<?= e(asset('assets/images/favicon-32x32.png')) ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?= e(asset('assets/images/favicon-16x16.png')) ?>" type="image/png" sizes="16x16">
<link rel="apple-touch-icon" href="<?= e(asset('assets/images/apple-touch-icon.png')) ?>">
<link rel="mask-icon" href="<?= e(asset('assets/images/favicon.svg')) ?>" color="#174A3A">
<meta name="theme-color" content="#F7F7F3">
<meta name="msapplication-TileColor" content="#174A3A">
<meta name="msapplication-config" content="none">