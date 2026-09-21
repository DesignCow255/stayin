<?php

declare(strict_types=1);

use App\Core\Env;

return [
    'default_locale' => Env::string('APP_LOCALE', 'en'),
    'fallback_locale' => 'en',
    'locales' => [
        'en' => ['label' => 'English', 'native' => 'English', 'flag' => '🇬🇧'],
        'sw' => ['label' => 'Swahili', 'native' => 'Kiswahili', 'flag' => '🇹🇿'],
    ],
    'display_timezone' => Env::string('APP_DISPLAY_TIMEZONE', 'Africa/Dar_es_Salaam'),
];