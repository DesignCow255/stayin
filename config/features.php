<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Feature flags. Everything defaulting to false must have a documented reason:
 * missing third-party credentials, incomplete implementation, or an operational
 * decision. Never flip a flag to true without the capability actually working.
 */
return [
    'social_login_google' => Env::bool('FEATURE_SOCIAL_LOGIN_GOOGLE', false),
    'social_login_apple' => Env::bool('FEATURE_SOCIAL_LOGIN_APPLE', false),
    'live_payments' => Env::bool('FEATURE_LIVE_PAYMENTS', false),
    'mock_payments' => Env::bool('FEATURE_MOCK_PAYMENTS', true),
    'host_subscriptions' => Env::bool('FEATURE_HOST_SUBSCRIPTIONS', false),
    'whatsapp_api' => Env::bool('FEATURE_WHATSAPP_API', false),
    'whatsapp_deeplink' => Env::bool('FEATURE_WHATSAPP_DEEPLINK', true),
    'sms_notifications' => Env::bool('FEATURE_SMS_NOTIFICATIONS', false),
    'push_notifications' => Env::bool('FEATURE_PUSH_NOTIFICATIONS', false),
    'live_fx_rates' => Env::bool('FEATURE_LIVE_FX_RATES', false),
    'property_verification' => Env::bool('FEATURE_PROPERTY_VERIFICATION', true),
    'reviews' => Env::bool('FEATURE_REVIEWS', true),
    'messaging' => Env::bool('FEATURE_MESSAGING', true),
    'newsletter' => Env::bool('FEATURE_NEWSLETTER', true),
    'blog' => Env::bool('FEATURE_BLOG', true),
    'map_provider' => Env::string('FEATURE_MAP_PROVIDER', 'none'), // none|leaflet_osm|google|mapbox
    'pwa' => Env::bool('FEATURE_PWA', true),
    'maintenance_mode' => Env::bool('MAINTENANCE_MODE', false),
];