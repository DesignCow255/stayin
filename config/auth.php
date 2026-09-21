<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'hash_algo' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT,
    'hash_options' => defined('PASSWORD_ARGON2ID')
        ? ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 2]
        : ['cost' => 12],

    'max_login_attempts' => 5,
    'lockout_minutes' => 15,
    'remember_days' => 30,

    'email_verification_ttl_hours' => 48,
    'password_reset_ttl_minutes' => 60,

    'roles' => [
        'guest' => 'Guest',
        'host' => 'Host',
        'support' => 'Support',
        'finance' => 'Finance',
        'admin' => 'Administrator',
        'super_admin' => 'Super Administrator',
    ],

    /*
     * Permission catalogue. `*` grants everything.
     * Format: permission => human label.
     */
    'permissions' => [
        'admin.access' => 'Access the admin control center',
        'users.view' => 'View users',
        'users.manage' => 'Create, edit, suspend and restore users',
        'hosts.manage' => 'Manage hosts and host accounts',
        'properties.view' => 'View all properties',
        'properties.moderate' => 'Verify, reject and suspend properties',
        'bookings.view' => 'View all bookings',
        'bookings.manage' => 'Override and manage bookings',
        'payments.view' => 'View payment transactions',
        'payments.refund' => 'Issue refunds',
        'finance.view' => 'View financial ledger and reports',
        'finance.manage' => 'Manage commissions, taxes, fees and settlements',
        'settlements.approve' => 'Approve and release host settlements',
        'reviews.moderate' => 'Moderate reviews',
        'messages.moderate' => 'Access conversations for moderation',
        'promotions.manage' => 'Manage promotions and promo codes',
        'subscriptions.manage' => 'Manage subscription plans and host subscriptions',
        'newsletter.manage' => 'Manage newsletter subscribers and campaigns',
        'cms.manage' => 'Manage hero slides, pages and content blocks',
        'seo.manage' => 'Manage SEO metadata and redirects',
        'settings.manage' => 'Manage system settings',
        'audit.view' => 'View audit logs',
        'system.health' => 'View system health',
    ],

    /*
     * Default role → permission mapping used by the seeder / role sync command.
     */
    'role_permissions' => [
        'super_admin' => ['*'],
        'admin' => [
            'admin.access', 'users.view', 'users.manage', 'hosts.manage', 'properties.view',
            'properties.moderate', 'bookings.view', 'bookings.manage', 'payments.view',
            'payments.refund', 'finance.view', 'finance.manage', 'settlements.approve', 'reviews.moderate',
            'messages.moderate', 'promotions.manage', 'subscriptions.manage', 'newsletter.manage',
            'cms.manage', 'seo.manage', 'audit.view', 'system.health',
        ],
        'support' => [
            'admin.access', 'users.view', 'properties.view', 'bookings.view', 'reviews.moderate',
            'messages.moderate', 'audit.view',
        ],
        'finance' => [
            'admin.access', 'payments.view', 'payments.refund', 'finance.view', 'finance.manage',
            'settlements.approve', 'bookings.view', 'audit.view',
        ],
        'host' => [],
        'guest' => [],
    ],

    /*
     * OAuth providers. Feature flags decide whether the buttons are active.
     * Real credentials arrive via environment variables only.
     */
    'oauth' => [
        'google' => [
            'enabled' => Env::bool('FEATURE_OAUTH_GOOGLE', false),
            'client_id' => Env::string('GOOGLE_CLIENT_ID', ''),
            'client_secret' => Env::string('GOOGLE_CLIENT_SECRET', ''),
            'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'userinfo_url' => 'https://openidconnect.googleapis.com/v1/userinfo',
            'scopes' => ['openid', 'email', 'profile'],
        ],
        'apple' => [
            'enabled' => Env::bool('FEATURE_OAUTH_APPLE', false),
            'client_id' => Env::string('APPLE_CLIENT_ID', ''),
            'team_id' => Env::string('APPLE_TEAM_ID', ''),
            'key_id' => Env::string('APPLE_KEY_ID', ''),
            'private_key' => Env::string('APPLE_PRIVATE_KEY', ''),
            'authorize_url' => 'https://appleid.apple.com/auth/authorize',
            'token_url' => 'https://appleid.apple.com/auth/token',
            'scopes' => ['name', 'email'],
        ],
    ],
];