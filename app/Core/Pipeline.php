<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Middleware pipeline. Middleware may implement `handle(Request, callable): Response`
 * (Laravel-style, resolved from the container) and is executed inside-out.
 */
final class Pipeline
{
    private Request $request;

    /**
     * @param array<int, string> $defaultMiddleware
     */
    public function __construct(private array $defaultMiddleware = [])
    {
    }

    public function send(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param array<int, string> $middleware
     */
    public function through(array $middleware): self
    {
        $this->defaultMiddleware = array_values(array_unique([...$this->defaultMiddleware, ...$middleware]));
        return $this;
    }

    public function then(callable $destination): Response
    {
        $pipeline = array_reduce(
            array_reverse($this->defaultMiddleware),
            fn (callable $next, string $middleware): callable => fn (Request $request): Response => $this->carry($middleware, $request, $next),
            $destination
        );

        return $pipeline($this->request);
    }

    private function carry(string $middleware, Request $request, callable $next): Response
    {
        $instance = Container::get($middleware);

        if (method_exists($instance, 'handle')) {
            return $instance->handle($request, $next);
        }

        throw new \RuntimeException('Middleware ' . $middleware . ' does not implement handle().');
    }
}
