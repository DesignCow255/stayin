<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'rate_limits' => [
        'login' => ['attempts' => 5, 'decay_seconds' => 900],
        'register' => ['attempts' => 5, 'decay_seconds' => 3600],
        'password_reset' => ['attempts' => 3, 'decay_seconds' => 900],
        'contact' => ['attempts' => 5, 'decay_seconds' => 3600],
        'newsletter' => ['attempts' => 5, 'decay_seconds' => 3600],
        'api' => ['attempts' => 120, 'decay_seconds' => 60],
        'search' => ['attempts' => 90, 'decay_seconds' => 60],
        'booking' => ['attempts' => 20, 'decay_seconds' => 3600],
        'payment_init' => ['attempts' => 12, 'decay_seconds' => 3600],
        'hold_create' => ['attempts' => 30, 'decay_seconds' => 3600],
    ],
    'password' => [
        'min_length' => 10,
        'algorithm' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT,
    ],
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=(), payment=(self)',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ],
    'csp' => [
        'enabled' => Env::bool('CSP_ENABLED', true),
        'report_only' => Env::bool('CSP_REPORT_ONLY', false),
        'extra_script_src' => array_values(array_filter(explode(',', Env::string('CSP_EXTRA_SCRIPT_SRC', '')))),
        'extra_connect_src' => array_values(array_filter(explode(',', Env::string('CSP_EXTRA_CONNECT_SRC', '')))),
        'extra_frame_src' => array_values(array_filter(explode(',', Env::string('CSP_EXTRA_FRAME_SRC', '')))),
        'extra_img_src' => array_values(array_filter(explode(',', Env::string('CSP_EXTRA_IMG_SRC', 'https:')))),
    ],
    'force_https' => Env::bool('FORCE_HTTPS', false),
    'hsts' => Env::bool('HSTS_ENABLED', false),
];