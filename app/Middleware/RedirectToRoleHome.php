<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class RedirectToRoleHome implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::check()) {
            throw HttpException::redirect(AuthService::home(), 'Opening your dashboard.');
        }

        return $next($request);
    }
}