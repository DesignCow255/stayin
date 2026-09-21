<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class EnsureGuest implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::anyRole(['guest'])) {
            return $next($request);
        }

        throw HttpException::redirect(AuthService::home(), 'Your account dashboard is separate from the guest area.');
    }
}