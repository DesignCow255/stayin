<?php

declare(strict_types=1);

use App\Core\Env;

return [
    /*
     | Default gateway. `mock` is development-only and refuses to run in
     | production environments (see MockGateway::isAvailable()).
     */
    'default' => Env::string('PAYMENT_DEFAULT_GATEWAY', 'mock'),

    'gateways' => [
        'mock' => [
            'driver' => App\Payments\Gateways\MockGateway::class,
            'label' => 'Development Mock Gateway',
            'enabled' => Env::bool('PAYMENT_MOCK_ENABLED', true),
            'live' => false,
            'currencies' => ['TZS', 'USD'],
        ],
        'mpesa' => [
            'driver' => App\Payments\Gateways\MobileMoneyGateway::class,
            'label' => 'M-Pesa',
            'enabled' => Env::bool('PAYMENT_MPESA_ENABLED', false),
            'live' => true,
            'provider' => 'vodacom_tanzania',
            'currencies' => ['TZS'],
            'config' => [
                'api_key' => Env::string('MPESA_API_KEY', ''),
                'public_key' => Env::string('MPESA_PUBLIC_KEY', ''),
                'service_provider_code' => Env::string('MPESA_SERVICE_PROVIDER_CODE', ''),
                'endpoint' => Env::string('MPESA_ENDPOINT', ''),
                'webhook_secret' => Env::string('MPESA_WEBHOOK_SECRET', ''),
            ],
        ],
        'mixx_by_yas' => [
            'driver' => App\Payments\Gateways\MobileMoneyGateway::class,
            'label' => 'Mixx by Yas',
            'enabled' => Env::bool('PAYMENT_MIXX_ENABLED', false),
            'live' => true,
            'provider' => 'yas_tanzania',
            'currencies' => ['TZS'],
            'config' => [
                'api_key' => Env::string('MIXX_API_KEY', ''),
                'public_key' => Env::string('MIXX_PUBLIC_KEY', ''),
                'service_provider_code' => Env::string('MIXX_SERVICE_PROVIDER_CODE', ''),
                'endpoint' => Env::string('MIXX_ENDPOINT', ''),
                'webhook_secret' => Env::string('MIXX_WEBHOOK_SECRET', ''),
            ],
        ],
        'airtel_money' => [
            'driver' => App\Payments\Gateways\MobileMoneyGateway::class,
            'label' => 'Airtel Money',
            'enabled' => Env::bool('PAYMENT_AIRTEL_ENABLED', false),
            'live' => true,
            'provider' => 'airtel_africa',
            'currencies' => ['TZS'],
            'config' => [
                'api_key' => Env::string('AIRTEL_API_KEY', ''),
                'client_secret' => Env::string('AIRTEL_CLIENT_SECRET', ''),
                'endpoint' => Env::string('AIRTEL_ENDPOINT', ''),
                'webhook_secret' => Env::string('AIRTEL_WEBHOOK_SECRET', ''),
            ],
        ],
        'halopesa' => [
            'driver' => App\Payments\Gateways\MobileMoneyGateway::class,
            'label' => 'HaloPesa',
            'enabled' => Env::bool('PAYMENT_HALOPESA_ENABLED', false),
            'live' => true,
            'provider' => 'halotel_tanzania',
            'currencies' => ['TZS'],
            'config' => [
                'api_key' => Env::string('HALOPESA_API_KEY', ''),
                'endpoint' => Env::string('HALOPESA_ENDPOINT', ''),
                'webhook_secret' => Env::string('HALOPESA_WEBHOOK_SECRET', ''),
            ],
        ],
        'card' => [
            'driver' => App\Payments\Gateways\CardGateway::class,
            'label' => 'Visa / Mastercard',
            'enabled' => Env::bool('PAYMENT_CARD_ENABLED', false),
            'live' => true,
            'currencies' => ['TZS', 'USD'],
            'config' => [
                'public_key' => Env::string('CARD_PUBLIC_KEY', ''),
                'secret_key' => Env::string('CARD_SECRET_KEY', ''),
                'endpoint' => Env::string('CARD_ENDPOINT', ''),
                'webhook_secret' => Env::string('CARD_WEBHOOK_SECRET', ''),
            ],
        ],
    ],

    'webhook' => [
        'tolerance_seconds' => Env::int('PAYMENT_WEBHOOK_TOLERANCE', 300),
        'log_dir' => Env::string('APP_BASE_PATH', dirname(__DIR__)) . '/storage/private/payment_webhooks',
    ],

    'commission' => [
        'default_percent' => Env::string('COMMISSION_DEFAULT_PERCENT', '10.00'),
    ],
];