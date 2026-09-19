<?php

declare(strict_types=1);

/**
 * StayIn front controller.
 *
 * All HTTP traffic enters here. Nothing outside public/ is intended to be
 * web-accessible in production (see public/.htaccess and docs/DEPLOYMENT.md).
 */

use App\Core\App;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\RequestContext;
use App\Core\Router;

$basePath = require dirname(__DIR__) . '/bootstrap/app.php';

$router = new Router($basePath);
$router->load($basePath . '/routes/web.php');
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
    $response = ErrorHandler::render($e instanceof App\Core\HttpException ? $e->status : 500, $request, $e);
}

if ($request->expectsJson()) {
    $response = $response->withHeader('X-Request-Id', RequestContext::id());
}

$response->send();