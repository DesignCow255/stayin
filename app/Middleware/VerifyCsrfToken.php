<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Blocks cross-site state-changing requests. All POST/PUT/PATCH/DELETE
 * browser requests must carry a valid token.
 */
final class VerifyCsrfToken implements MiddlewareInterface
{
    public function handle(Request $request, array $params = []): ?Response
    {
        $method = $request->method();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return null;
        }

        $token = (string) ($request->header('X-CSRF-Token') ?? $request->input('_token', '') ?? '');

        if (Csrf::isValid($token)) {
            return null;
        }

        Session::flashErrors(['form' => 'Your session expired. Please try again.']);

        if ($request->expectsJson()) {
            return Response::json([
                'error' => 'csrf_token_invalid',
                'message' => 'Your session expired. Please refresh the page and try again.',
            ], 419);
        }

        return Response::redirect($request->header('Referer') ?? '/', 303);
    }
}
