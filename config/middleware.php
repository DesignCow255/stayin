<?php

declare(strict_types=1);

use App\Core\Env;

return [
    /*
     | Middleware aliases usable in route definitions.
     */
    'aliases' => [
        'guest' => App\Middleware\RedirectIfAuthenticated::class,
        'auth' => App\Middleware\Authenticate::class,
        'verified' => App\Middleware\EnsureEmailVerified::class,
        'csrf' => App\Middleware\VerifyCsrfToken::class,
        'role' => App\Middleware\EnsureRole::class,
        'permission' => App\Middleware\EnsurePermission::class,
        'host' => App\Middleware\EnsureHost::class,
        'admin' => App\Middleware\EnsureAdminAccess::class,
        'throttle' => App\Middleware\ThrottleRequests::class,
        'security_headers' => App\Middleware\SecurityHeaders::class,
        'locale' => App\Middleware\SetLocale::class,
        'track_activity' => App\Middleware\TrackLastActivity::class,
        'maintenance' => App\Middleware\CheckMaintenanceMode::class,
    ],

    'groups' => [
        'web' => [
            App\Middleware\SecurityHeaders::class,
            App\Middleware\SetLocale::class,
            App\Middleware\ShareViewData::class,
            App\Middleware\TrackLastActivity::class,
            App\Middleware\CheckMaintenanceMode::class,
        ],
        'auth' => [
            App\Middleware\Authenticate::class,
        ],
        'admin' => [
            App\Middleware\Authenticate::class,
            App\Middleware\EnsureAdminAccess::class,
        ],
        'host' => [
            App\Middleware\Authenticate::class,
            App\Middleware\EnsureHost::class,
        ],
        'api' => [
            App\Middleware\ThrottleRequests::class . ':api',
        ],
    ],

    'default_rate_limit' => 'api',
    'trusted_proxies' => Env::bool('TRUST_PROXY_HEADERS', false) ? ['*'] : [],
];