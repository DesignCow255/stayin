<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

final class TrackLastActivity implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (Session::has('_auth_id')) {
            Session::put('_last_seen', time());
        }
        return $next($request);
    }
}

