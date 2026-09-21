<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP middleware contract. Return a Response to short-circuit the pipeline,
 * or call $next($request) (and return its Response) to continue.
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
