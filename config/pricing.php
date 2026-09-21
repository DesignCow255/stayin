<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Pricing, currency and commission configuration.
 *
 * All monetary maths runs in minor units (integers) or BCMath-free DECIMAL
 * rounding — never PHP floats.
 */
return [
    'base_currency' => Env::string('CURRENCY_BASE', 'TZS'),
    'default_display_currency' => Env::string('CURRENCY_DEFAULT_DISPLAY', 'TZS'),
    'supported' => ['TZS', 'USD'],

    'minor_units' => [
        'TZS' => 2,
        'USD' => 2,
        'KES' => 2,
        'EUR' => 2,
        'GBP' => 2,
    ],

    'symbols' => [
        'TZS' => 'Tshs.',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'KES' => 'KSh',
    ],

    'fx' => [
        'provider' => Env::string('FX_PROVIDER', 'manual'),
        'cache_seconds' => Env::int('FX_CACHE_SECONDS', 21600),
        'stale_after_seconds' => Env::int('FX_STALE_AFTER_SECONDS', 604800),
        'fallback' => [
            'USD_TZS' => (float) Env::string('FX_FALLBACK_USD_TZS', '2643.562101'),
            'TZS_USD' => (float) Env::string('FX_FALLBACK_TZS_USD', '0.000378'),
        ],
    ],

    'commission' => [
        'default_percent' => (float) Env::string('COMMISSION_DEFAULT_PERCENT', '10.00'),
        'min_percent' => 0.0,
        'max_percent' => 35.0,
    ],

    'service_fee' => [
        'guest_percent' => (float) Env::string('SERVICE_FEE_GUEST_PERCENT', '0.00'),
        'min_minor' => 0,
    ],

    'rounding' => [
        // Round the stored amount to the currency minor unit (2 = cents/percent).
        'mode' => 'half_up',
        'scale' => 2,
    ],
];