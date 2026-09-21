<?php

declare(strict_types=1);

/**
 * StayIn front controller.
 *
 * All HTTP traffic enters here. Nothing outside public/ is intended to be
 * web-accessible in production (see public/.htaccess and docs/DEPLOYMENT.md).
 */

// --- Load bootstrap first to initialize autoloader and core services ---
// This ensures that classes like App\Core\Config are available via autoloading
// before helper functions or other application logic is executed.
$basePath = require dirname(__DIR__) . '/bootstrap/app.php';
// --- End bootstrap loading ---

use App\Core\App;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\RequestContext;
use App\Core\Router;

// Session management & interaction logging — applied to the global web group
// so authenticated and anonymous requests are traceable (§5).
use App\Middleware\LogUserSession;

// --- Custom addition for subdirectory ---
// Adjust $_SERVER variables to remove the subdirectory prefix if present.
// This helps the router correctly match routes when the app is not in the web root.
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$appUrl = config('app.url'); // e.g., http://localhost/stayin
$parsedAppUrl = parse_url($appUrl);
$appBasePathSegment = $parsedAppUrl['path'] ?? '/'; // e.g., /stayin

// Remove trailing slash from base path segment for consistent comparison
$appBasePathSegment = rtrim($appBasePathSegment, '/');

// Check if the request URI starts with the application's base path segment
if ($appBasePathSegment !== '' && str_starts_with($requestUri, $appBasePathSegment)) {
    // Strip the base path segment from the request URI
    $strippedPath = substr($requestUri, strlen($appBasePathSegment));

    // Ensure the resulting path starts with a '/' and is not empty
    if ($strippedPath === '') {
        $strippedPath = '/';
    } elseif (!str_starts_with($strippedPath, '/')) {
        $strippedPath = '/' . $strippedPath;
    }

    // Update SERVER variables to reflect the stripped path
    $_SERVER['REQUEST_URI'] = $strippedPath;
    // If PATH_INFO is used, adjust it too, though less common for frameworks
    if (isset($_SERVER['PATH_INFO']) && str_starts_with($_SERVER['PATH_INFO'], $appBasePathSegment)) {
         $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], strlen($appBasePathSegment)) ?: '/';
    }
}
// --- End of custom addition ---

if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/'));
    if ($file && str_starts_with($file, __DIR__ . '/') && is_file($file) && !str_ends_with($file, '.php')) return false;
}

$router = new Router($basePath);
$router->group(['middleware' => [\App\Middleware\ShareViewData::class, \App\Middleware\LogUserSession::class]], static function (Router $router) use ($basePath): void {
    $router->load($basePath . '/routes/web.php');
});
$router->load($basePath . '/routes/api.php');

App::setRouter($router);

$request = Request::capture();
App::setCurrentRequest($request);

try {
    $response = $router->dispatch($request);
} catch (Throwable $e) {
    Logger::error('http.unhandled', [
        'class' => $e::class,
        'message' => $e->getMessage(),
    ]);
    $response = ErrorHandler::render($e instanceof \App\Core\HttpException ? $e->status : 500, $request, $e);
}

if ($request->expectsJson()) {
    $response = $response->withHeader('X-Request-Id', RequestContext::id());
}

$response->send();
