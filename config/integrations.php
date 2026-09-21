<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Third-party integration credentials. All values are empty by default: no
 * integration claims to work until credentials are supplied and verified.
 */
return [
    'google' => [
        'client_id' => Env::string('GOOGLE_CLIENT_ID', ''),
        'client_secret' => Env::string('GOOGLE_CLIENT_SECRET', ''),
        'redirect_uri' => Env::string('GOOGLE_REDIRECT_URI', ''),
    ],
    'apple' => [
        'client_id' => Env::string('APPLE_CLIENT_ID', ''),
        'team_id' => Env::string('APPLE_TEAM_ID', ''),
        'key_id' => Env::string('APPLE_KEY_ID', ''),
        'private_key_path' => Env::string('APPLE_PRIVATE_KEY_PATH', ''),
        'redirect_uri' => Env::string('APPLE_REDIRECT_URI', ''),
    ],
    'maps' => [
        'provider' => Env::string('MAP_PROVIDER', 'none'),
        'google_api_key' => Env::string('GOOGLE_MAPS_API_KEY', ''),
        'mapbox_token' => Env::string('MAPBOX_TOKEN', ''),
    ],
    'whatsapp' => [
        'support_number' => Env::string('WHATSAPP_SUPPORT_NUMBER', ''),
        'default_message' => Env::string('WHATSAPP_DEFAULT_MESSAGE', 'Hello StayIn, I would like some help.'),
        'api_phone_number_id' => Env::string('WHATSAPP_API_PHONE_NUMBER_ID', ''),
        'api_token' => Env::string('WHATSAPP_API_TOKEN', ''),
    ],
    'sms' => [
        'provider' => Env::string('SMS_PROVIDER', 'log'),
        'api_key' => Env::string('SMS_API_KEY', ''),
        'sender_id' => Env::string('SMS_SENDER_ID', 'STAYIN'),
    ],
    'analytics' => [
        'ga4_measurement_id' => Env::string('GA4_MEASUREMENT_ID', ''),
        'first_party' => Env::bool('ANALYTICS_FIRST_PARTY', true),
    ],
];