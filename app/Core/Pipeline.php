<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware pipeline. Middleware classes implement `handle(Request, callable)`
 * (see MiddlewareInterface) and are executed in registration order.
 */
final class Pipeline
{
    /** @var array<int, string> */
    private array $middleware = [];

    public function __construct(private Request $request)
    {
    }

    /**
     * @param array<int, string> $middleware
     */
    public function through(array $middleware): self
    {
        $this->middleware = [...$this->middleware, ...$middleware];
        return $this;
    }

    public function then(callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn (callable $next, string $middleware): callable => fn (Request $request): Response => $this->carry($middleware, $request, $next),
            $destination
        );

        return $pipeline($this->request);
    }

    private function carry(string $middleware, Request $request, callable $next): Response
    {
        $instance = Container::get($middleware);
        if (property_exists($instance, 'parameters')) {
            $instance::$parameters = isset(explode(':', $middleware, 2)[1]) ? explode(',', explode(':', $middleware, 2)[1]) : [];
        }

        // Optional ":param" suffix (e.g. ThrottleRequests:api) is resolved by
        // the middleware itself from the route; nothing to parse here.

        if (method_exists($instance, 'handle')) {
            $result = $instance->handle($request, $next);
            return $result instanceof Response ? $result : $next($request);
        }

        throw new \RuntimeException('Middleware ' . $middleware . ' does not implement handle().');
    }
}

