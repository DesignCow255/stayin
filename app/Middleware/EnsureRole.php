<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Requires any of the configured roles. Roles are passed via the route
 * definition, e.g. ->middleware(['role:admin,super_admin']) — the parameters
 * are resolved from the middleware alias key at dispatch time via Config.
 */
final class EnsureRole implements MiddlewareInterface
{
    /** @var array<int, string> */
    public static array $parameters = [];

    public function handle(Request $request, callable $next): Response
    {
        $required = self::$parameters !== [] ? self::$parameters : ['admin', 'super_admin'];

        if (AuthService::anyRole($required)) {
            return $next($request);
        }

        throw HttpException::forbidden('You do not have access to this area.');
    }
}
