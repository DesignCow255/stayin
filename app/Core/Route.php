<?php

declare(strict_types=1);

namespace App\Core;

/**
 * A single registered route.
 */
final class Route
{
    /** @var array<int, string> */
    private array $middleware = [];
    private ?string $name = null;

    /**
     * @param array<int, string> $methods
     * @param array<int, string> $paramNames
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $pattern,
        public string $regex,
        public readonly array $paramNames,
        public readonly mixed $handler,
        public readonly array $defaults = []
    ) {
    }

    public function middleware(string|array $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function routeName(): ?string
    {
        return $this->name;
    }

    /**
     * @return array<int, string>
     */
    public function middlewareList(): array
    {
        return $this->middleware;
    }
}