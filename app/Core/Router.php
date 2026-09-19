<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Framework-free HTTP router.
 *
 * Features: static + parameterised routes, named routes, route groups with
 * shared prefix/middleware, middleware aliases & groups, 404/405 handling.
 *
 * Routes are registered in routes/web.php and routes/api.php via Router::load().
 */
final class Router
{
    /** @var array<int, Route> */
    private array $routes = [];

    /** @var array<string, int> */
    private array $named = [];

    /** @var array<int, string> */
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, array<int, string>> */
    private array $groups = [];

    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? Config::string('app.base_path', dirname(__DIR__, 2));

        $this->aliases = (array) Config::get('middleware.aliases', []);
        $this->groups = (array) Config::get('middleware.groups', []);
    }

    /**
     * Load a route definition file. The file receives $router in scope.
     */
    public function load(string $file): self
    {
        if (!is_file($file)) {
            throw new \RuntimeException('Route file not found: ' . $file);
        }

        $router = $this;
        require $file;

        return $this;
    }

    // -----------------------------------------------------------------------
    // Registration
    // -----------------------------------------------------------------------

    public function get(string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(['GET'], $uri, $handler, $name, $middleware);
    }

    public function post(string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(['POST'], $uri, $handler, $name, $middleware);
    }

    public function put(string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(['PUT'], $uri, $handler, $name, $middleware);
    }

    public function patch(string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(['PATCH'], $uri, $handler, $name, $middleware);
    }

    public function delete(string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(['DELETE'], $uri, $handler, $name, $middleware);
    }

    /**
     * Register a route answering several verbs.
     *
     * @param array<int, string> $methods
     * @param array<int, string> $middleware
     */
    public function match(array $methods, string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        return $this->add(array_map('strtoupper', $methods), $uri, $handler, $name, $middleware);
    }

    /**
     * @param array<int, string> $methods
     * @param array<int, string> $middleware
     */
    public function add(array $methods, string $uri, mixed $handler, ?string $name = null, array $middleware = []): RouteDefinition
    {
        $pattern = $this->normaliseUri($this->groupPrefix . $uri);
        [$regex, $paramNames] = $this->compile($pattern);

        $route = new Route(
            methods: $methods,
            pattern: $pattern,
            regex: $regex,
            paramNames: $paramNames,
            handler: $handler,
            defaults: []
        );

        $route->middleware($this->groupMiddleware);
        if ($middleware !== []) {
            $route->middleware($middleware);
        }

        $index = count($this->routes);
        $this->routes[$index] = $route;

        if ($name !== null) {
            $route->name($name);
            $this->named[$name] = $index;
        }

        return new RouteDefinition($this, $index);
    }

    /**
     * @param array{prefix?:string, middleware?:array<int, string>} $attributes
     */
    public function group(array $attributes, callable $callback): self
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix = rtrim($previousPrefix . ($attributes['prefix'] ?? ''), '/');
        $this->groupMiddleware = [...$previousMiddleware, ...($attributes['middleware'] ?? [])];

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;

        return $this;
    }

    /**
     * Used by RouteDefinition for fluent ->middleware().
     *
     * @param array<int, string> $middleware
     */
    public function appendMiddleware(int $index, array $middleware): void
    {
        if (isset($this->routes[$index])) {
            $this->routes[$index]->middleware($middleware);
        }
    }

    /**
     * Used by RouteDefinition for fluent ->name().
     */
    public function nameRoute(int $index, string $name): void
    {
        if (isset($this->routes[$index])) {
            $this->routes[$index]->name($name);
            $this->named[$name] = $index;
        }
    }
}