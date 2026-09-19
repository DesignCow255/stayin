<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'base' => Env::string('CURRENCY_BASE', 'TZS'),
    'supported' => [
        'TZS' => ['symbol' => 'Tshs', 'decimals' => 0, 'position' => 'prefix', 'name' => 'Tanzanian Shilling'],
        'USD' => ['symbol' => '$', 'decimals' => 2, 'position' => 'prefix', 'name' => 'US Dollar'],
        'EUR' => ['symbol' => '€', 'decimals' => 2, 'position' => 'prefix', 'name' => 'Euro'],
        'KES' => ['symbol' => 'KSh', 'decimals' => 0, 'position' => 'prefix', 'name' => 'Kenyan Shilling'],
        'UGX' => ['symbol' => 'USh', 'decimals' => 0, 'position' => 'prefix', 'name' => 'Ugandan Shilling'],
        'GBP' => ['symbol' => '£', 'decimals' => 2, 'position' => 'prefix', 'name' => 'Pound Sterling'],
    ],
    'default_display' => Env::string('CURRENCY_DEFAULT_DISPLAY', 'TZS'),
    'stale_after_hours' => Env::int('CURRENCY_STALE_HOURS', 24),
    'fx' => [
        'provider' => Env::string('FX_PROVIDER', 'manual'),
        'api_key' => Env::string('FX_API_KEY', ''),
        'endpoint' => Env::string('FX_ENDPOINT', ''),
        'manual_rates' => [
            'USD_TZS' => Env::string('FX_FALLBACK_USD_TZS', '2643.562101'),
            'TZS_USD' => Env::string('FX_FALLBACK_TZS_USD', '0.000378'),
        ],
    ],
];