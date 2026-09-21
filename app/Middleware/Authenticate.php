<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class Authenticate implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::check()) {
            return $next($request);
        }

        SessionTarget::storeIntended($request->path());

        if ($request->expectsJson()) {
            throw HttpException::forbidden('Authentication required.');
        }

        throw HttpException::redirect('/login', 'Please sign in to continue.');
    }
}
