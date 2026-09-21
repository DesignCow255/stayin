<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class EnsureHost implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (AuthService::anyRole(['host']) && !AuthService::anyRole(['admin', 'super_admin', 'support', 'finance'])) {
            return $next($request);
        }

        throw HttpException::redirect(AuthService::home(), 'A host account is required for this area.');
    }
}
