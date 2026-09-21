<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Security;

final class SecurityHeaders implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        Security::applyHeaders();
        return $next($request);
    }
}
