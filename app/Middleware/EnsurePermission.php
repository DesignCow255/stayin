<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Services\Gate;

final class EnsurePermission implements MiddlewareInterface
{
    /** @var array<int, string> */
    public static array $parameters = [];

    public function handle(Request $request, callable $next): Response
    {
        $required = self::$parameters !== [] ? self::$parameters : ['admin.access'];

        foreach ($required as $permission) {
            if (!Gate::allows($permission)) {
                throw HttpException::forbidden('Missing permission: ' . $permission);
            }
        }

        return $next($request);
    }
}
