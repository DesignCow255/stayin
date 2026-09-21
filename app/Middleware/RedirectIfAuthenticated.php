<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::check()) {
            throw HttpException::redirect(AuthService::home(), 'Already signed in.');
        }
        return $next($request);
    }
}
