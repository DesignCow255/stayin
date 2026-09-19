<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

final class EnsureEmailVerified implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $user = AuthService::user();

        if ($user === null || ($user['email_verified_at'] ?? null) !== null) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            throw HttpException::forbidden('Please verify your email address first.');
        }

        throw HttpException::redirect('/verify-email', 'Please verify your email address to continue.');
    }
}
