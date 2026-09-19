<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuthService;

/**
 * Shares global template variables (brand, auth user, csrf, locale, flash)
 * so every view has consistent context.
 */
final class ShareViewData implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        View::share('appName', Config::string('app.name', 'StayIn'));
        View::share('appUrl', Config::string('app.url'));
        View::share('locale', $GLOBALS['stayin_locale'] ?? Config::string('locale.default_locale', 'en'));
        View::share('displayTimezone', Config::string('locale.display_timezone', 'Africa/Dar_es_Salaam'));
        View::share('authUser', AuthService::user());
        View::share('authRoles', AuthService::roles());
        View::share('csrfToken', Csrf::token());
        View::share('currentPath', $request->path());
        View::share('flashStatus', flash_status());

        return $next($request);
    }
}
