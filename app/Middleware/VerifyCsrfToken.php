<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Blocks cross-site state-changing requests. All POST/PUT/PATCH/DELETE
 * browser requests must carry a valid synchroniser token.
 */
final class VerifyCsrfToken implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $method = $request->method();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $token = (string) ($request->input(Csrf::FIELD) ?? $request->header(Csrf::HEADER) ?? '');

        if (Csrf::verify($token)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return Response::json([
                'ok' => false,
                'error' => [
                    'code' => 'csrf_token_invalid',
                    'message' => 'Your session expired. Please refresh the page and try again.',
                ],
            ], 419);
        }

        Session::flashErrors(['form' => 'Your session expired. Please try again.']);
        Session::flashInput($request->all());

        return Response::html(\App\Core\ErrorHandler::plainPage(419), 419);
    }
}

