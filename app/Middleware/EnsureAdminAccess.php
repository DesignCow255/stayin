<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\Gate;

final class EnsureAdminAccess implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (Gate::allows('admin.access')) {
            return $next($request);
        }

        throw HttpException::forbidden('Administrator access is required for this area.');
    }
}
