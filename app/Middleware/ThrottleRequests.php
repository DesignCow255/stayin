<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Services\RateLimiter;

final class ThrottleRequests implements MiddlewareInterface
{
    /** @var array<int, string> */
    public static array $parameters = [];

    public function handle(Request $request, callable $next): Response
    {
        $limitName = self::$parameters[0] ?? 'api';
        $fingerprint = $request->ip() . '|' . substr((string) ($request->header('User-Agent') ?? ''), 0, 120);

        if (!RateLimiter::attempt($limitName, $fingerprint)['allowed']) {
            throw HttpException::tooManyRequests();
        }

        return $next($request);
    }
}
