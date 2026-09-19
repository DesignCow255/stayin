<?php

declare(strict_types=1);

use App\Core\Env;

$basePath = dirname(__DIR__);

$appUrl = Env::string('APP_URL', 'http://localhost/stayin/public');

return [
    'name' => Env::string('APP_NAME', 'StayIn'),
    'env' => Env::string('APP_ENV', 'local'),
    'debug' => Env::bool('APP_DEBUG', true),
    'url' => rtrim($appUrl, '/'),
    'asset_url' => rtrim(Env::string('ASSET_URL', $appUrl), '/'),
    'cdn_url' => rtrim(Env::string('CDN_URL', ''), '/'),
    'base_path' => $basePath,
    'key' => Env::string('APP_KEY', ''),
    'timezone' => Env::string('APP_TIMEZONE', 'UTC'),
    'display_timezone' => Env::string('APP_DISPLAY_TIMEZONE', 'Africa/Dar_es_Salaam'),
    'locale' => Env::string('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
    'available_locales' => ['en' => 'English', 'sw' => 'Kiswahili'],
    'trust_proxy_headers' => Env::bool('TRUST_PROXY_HEADERS', false),
    'session_name' => Env::string('SESSION_NAME', 'stayin_session'),
    'session_lifetime_minutes' => Env::int('SESSION_LIFETIME', 120),
    'session_secure' => Env::bool('SESSION_SECURE', false),
    'session_path' => Env::string('SESSION_PATH', $basePath . '/storage/sessions'),
    'log_level' => Env::string('LOG_LEVEL', 'debug'),
    'support' => [
        'email' => Env::string('SUPPORT_EMAIL', 'support@stayin.example'),
        'phone' => Env::string('SUPPORT_PHONE', '+255 000 000 000'),
        'whatsapp' => Env::string('WHATSAPP_SUPPORT_NUMBER', ''),
        'whatsapp_message' => Env::string(
            'WHATSAPP_DEFAULT_MESSAGE',
            'Hello StayIn, I would like help with my booking.'
        ),
    ],
    'maintenance' => Env::bool('MAINTENANCE_MODE', false),
];