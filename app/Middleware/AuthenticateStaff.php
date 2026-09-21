<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class AuthenticateStaff implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!AuthService::check()) {
            SessionTarget::storeIntended($request->path());

            if ($request->expectsJson()) {
                throw HttpException::forbidden('Authentication required.');
            }

            throw HttpException::redirect(
                '/control/login',
                'Administrative sign in is required.'
            );
        }

        if (!AuthService::anyRole(['admin', 'super_admin'])) {
            throw HttpException::forbidden(
                'Administrator access is required for this area.'
            );
        }

        return $next($request);
    }
}
