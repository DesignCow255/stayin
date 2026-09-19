<?php

declare(strict_types=1);

/**
 * StayIn application bootstrap.
 *
 * Boot order: autoloader → environment → configuration → timezone →
 * error handling → logging → cache → views → request context.
 *
 * Returns the absolute application base path.
 */

use App\Core\Cache;
use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\RequestContext;
use App\Core\View;

$basePath = dirname(__DIR__);

// ---------------------------------------------------------------------------
// 1. Autoloading (Composer when available, otherwise the bundled PSR-4 loader)
// ---------------------------------------------------------------------------
$composer = $basePath . '/vendor/autoload.php';
if (is_file($composer)) {
    require $composer;
} else {
    require $basePath . '/app/Core/Autoloader.php';
    App\Core\Autoloader::register();
}

if (!function_exists('e')) {
    require $basePath . '/app/Support/helpers.php';
}

// ---------------------------------------------------------------------------
// 2. Environment
// ---------------------------------------------------------------------------
Env::load($basePath . '/.env');

// ---------------------------------------------------------------------------
// 3. Configuration
// ---------------------------------------------------------------------------
foreach (
    [
        'app', 'database', 'booking', 'currency', 'locale', 'mail',
        'security', 'middleware', 'features', 'integrations', 'payments', 'seo',
        'auth', 'pricing', 'support', 'display',
    ] as $configFile
) {
    Config::load($configFile, $basePath . '/config/' . $configFile . '.php');
}

// Expose the resolved base path so other config/services can build paths safely.
Config::get('app.base_path') ?? Config::set('app.base_path', $basePath);
Config::set('app.base_path', Config::string('app.base_path', $basePath));

// ---------------------------------------------------------------------------
// 4. Runtime settings
// ---------------------------------------------------------------------------
date_default_timezone_set(Config::string('app.timezone', 'UTC'));
mb_internal_encoding('UTF-8');
setlocale(LC_NUMERIC, 'C'); // deterministic decimal formatting

// ---------------------------------------------------------------------------
// 5. Error handling / logging / services
// ---------------------------------------------------------------------------
ErrorHandler::register();
RequestContext::boot();

Logger::boot(
    $basePath . '/storage/logs',
    Config::string('app.log_level', Config::bool('app.debug', false) ? 'debug' : 'warning')
);

Cache::boot($basePath . '/storage/cache');
View::boot($basePath . '/resources/views');

foreach (['logs', 'cache', 'sessions', 'uploads', 'private'] as $directory) {
    $path = $basePath . '/storage/' . $directory;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

return $basePath;