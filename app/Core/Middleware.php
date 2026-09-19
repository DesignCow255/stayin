<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base middleware. Subclasses implement handle() and may short-circuit by
 * returning a Response without invoking $next.
 */
abstract class Middleware
{
    /**
     * @param callable(Request):Response $next
     */
    abstract public function handle(Request $request, callable $next): Response;
}
