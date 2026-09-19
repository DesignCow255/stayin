<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Fluent helper returned by Router::get()/post()/... for naming & constraints.
 */
final class RouteRegistrar
{
    public function __construct(private Router $router, private int $index)
    {
    }

    public function name(string $name): self
    {
        $this->router->setName($this->index, $name);
        return $this;
    }

    /**
     * @param array<string, string> $constraints
     */
    public function where(array $constraints): self
    {
        $this->router->setConstraints($this->index, $constraints);
        return $this;
    }

    public function middleware(string ...$middleware): self
    {
        $this->router->addMiddleware($this->index, $middleware);
        return $this;
    }
}
