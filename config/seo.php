<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'site_name' => Env::string('SEO_SITE_NAME', 'StayIn'),
    'default_title' => Env::string('SEO_DEFAULT_TITLE', 'StayIn — Book distinctive stays across Tanzania'),
    'title_separator' => ' · ',
    'default_description' => Env::string(
        'SEO_DEFAULT_DESCRIPTION',
        'Discover and book verified hotels, lodges, villas and guest houses across Tanzania. '
        . 'Real availability, transparent pricing in Tshs and USD, and secure mobile money payments.'
    ),
    'default_image' => Env::string('SEO_DEFAULT_IMAGE', 'assets/images/og-default.png'),
    'twitter_handle' => Env::string('SEO_TWITTER_HANDLE', ''),
    'organisation' => [
        'legal_name' => Env::string('ORG_LEGAL_NAME', ''),
        'registration_number' => Env::string('ORG_REGISTRATION_NUMBER', ''),
        'tin' => Env::string('ORG_TIN', ''),
        'address' => Env::string('ORG_ADDRESS', 'Dar es Salaam, Tanzania'),
        'email' => Env::string('SUPPORT_EMAIL', ''),
        'phone' => Env::string('SUPPORT_PHONE', ''),
    ],
    'sitemap' => [
        'max_urls_per_file' => Env::int('SITEMAP_MAX_URLS', 20000),
        'cache_hours' => Env::int('SITEMAP_CACHE_HOURS', 6),
    ],
    'index_filters' => Env::bool('SEO_INDEX_FILTER_COMBOS', false),
    'robots' => [
        'disallow' => [
            '/admin',
            '/control',
            '/host/',
            '/guest/',
            '/checkout',
            '/api/',
            '/login',
            '/register',
            '/password/',
            '/health',
            '/search?',
        ],
    ],
];