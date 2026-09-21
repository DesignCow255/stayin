<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

final class CheckMaintenanceMode implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!config('app.maintenance', false)) {
            return $next($request);
        }

        // Admin, health and asset paths remain reachable for operators.
        foreach (['/admin', '/health', '/assets/'] as $prefix) {
            if (str_starts_with($request->path(), $prefix)) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return Response::json([
                'ok' => false,
                'error' => ['code' => 'maintenance', 'message' => 'StayIn is under maintenance. Please try again shortly.'],
            ], 503);
        }

        return Response::html(View503::render(), 503);
    }
}
