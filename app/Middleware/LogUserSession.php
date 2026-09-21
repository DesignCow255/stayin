<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Session management & interaction logging (§5).
 *
 * Ensures the PHP session is active, records the hit against the `user_sessions`
 * table for authenticated users (so concurrent sessions / activity can be
 * traced), and writes an interaction row for each authenticated request.
 * Non-authenticated hits still get a session-id-bound row for traceability.
 *
 * Applied to the global `web` middleware group so every routed request is
 * captured regardless of role.
 */
final class LogUserSession implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Ensure the session is running for this request.
        if (session_status() === PHP_SESSION_NONE) {
            Session::start();
        }

        $response = $next($request);

        $sid = (string) (Session::get('sid') ?? session_id());
        $userId = Session::get('_auth_id');
        $isAuth = is_int($userId) ? (int) $userId : null;

        // Skip logging for obviously noisy asset requests.
        $path = $request->path();
        if ($this->isNoise($path)) {
            return $response;
        }

        try {
            Database::insert('user_sessions', [
                'session_id' => $sid,
                'user_id' => $isAuth,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $path,
                'method' => $request->method(),
                'query_string' => $request->query() !== [] ? http_build_query($request->query()) : null,
                'referer' => $request->header('Referer') ?? null,
                'response_status' => $response->status(),
            ]);
        } catch (\Throwable $e) {
            // Session logging must never take the request down.
        }

        return $response;
    }

    private function isNoise(string $path): bool
    {
        $path = ltrim($path, '/');
        return str_starts_with($path, 'assets/')
            || str_ends_with($path, '.css')
            || str_ends_with($path, '.js')
            || str_ends_with($path, '.png')
            || str_ends_with($path, '.jpg')
            || str_ends_with($path, '.jpeg')
            || str_ends_with($path, '.svg')
            || str_ends_with($path, '.ico')
            || str_ends_with($path, '.webp');
    }
}
