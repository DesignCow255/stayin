<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Fluent route definition returned by Router::get()/post()/... so routes can
 * declare names and middleware inline.
 */
final class RouteDefinition
{
    public function __construct(private Router $router, private int $index)
    {
    }

    public function middleware(string|array $middleware): self
    {
        $this->router->appendMiddleware($this->index, (array) $middleware);
        return $this;
    }

    public function name(string $name): self
    {
        $this->router->nameRoute($this->index, $name);
        return $this;
    }
}