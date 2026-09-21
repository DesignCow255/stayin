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

    private string $basePath;

    private static ?string $currentRouteName = null;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? Config::string('app.base_path', dirname(__DIR__, 2));
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

    // -----------------------------------------------------------------------
    // Matching / dispatch
    // -----------------------------------------------------------------------

    public function dispatch(Request $request): Response
    {
        $method = $request->spoofedMethod();
        $path = $request->path();
        $allowed = [];

        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $path, $matches) !== 1) {
                continue;
            }

            if (!in_array($method, $route->methods, true)) {
                $allowed = [...$allowed, ...$route->methods];
                continue;
            }

            $params = [];
            foreach ($route->paramNames as $name) {
                $params[$name] = rawurldecode((string) ($matches[$name] ?? ''));
            }

            return $this->run($route, $request, $params);
        }

        if ($allowed !== []) {
            return ErrorHandler::render(405, $request, new MethodNotAllowedException(array_values(array_unique($allowed))))->withHeader('Allow', implode(', ', array_unique($allowed)));
        }

        return ErrorHandler::render(404, $request, new RouteNotFoundException('No route matches ' . $path));
    }

    private function run(Route $route, Request $request, array $params): Response
    {
        self::$currentRouteName = $route->routeName();
        $request->setRouteParams($params);
        Security::applyHeaders();

        // CSRF protection is a platform invariant for state-changing verbs.
        $middleware = $route->middlewareList();
        if ($request->spoofedMethod() !== 'GET'
            && !in_array('csrf', $middleware, true)
        ) {
            array_unshift($middleware, 'csrf');
        }

        try {
            return (new Pipeline($request))
                ->through($middleware)
                ->then(fn (Request $request): Response => $this->invokeHandler($route, $request));
        } catch (HttpException $e) {
            if ($e->redirectTo !== null) {
                return Response::redirect($e->redirectTo, 302);
            }
            return ErrorHandler::render($e->status, $request, $e);
        } catch (MethodNotAllowedException $e) {
            return ErrorHandler::render(405, $request, $e);
        } catch (ValidationException $e) {
            return $this->renderValidationFailure($request, $e);
        } catch (BusinessException $e) {
            return $this->renderBusinessFailure($request, $e);
        }
    }

    public function constrain(int $index, array $constraints): void
    {
        $route = $this->routes[$index];
        foreach ($constraints as $name => $pattern) {
            $route->regex = str_replace('(?<' . $name . '>[^/]+)', '(?<' . $name . '>' . $pattern . ')', $route->regex);
        }
    }

    public function definitions(): array { return $this->routes; }

    private function invokeHandler(Route $route, Request $request): Response
    {
        $handler = $route->handler;

        if ($handler instanceof \Closure) {
            return $this->normaliseResult(($handler)($request));
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
        } elseif (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
        } else {
            throw new \RuntimeException('Unsupported route handler on ' . $route->pattern);
        }

        if (!str_starts_with($class, 'App\\')) $class = 'App\\Controllers\\' . $class;
        $controller = Container::get($class);
        if (!is_object($controller) || !method_exists($controller, $method)) {
            throw new \RuntimeException(sprintf('Handler %s::%s() does not exist.', $class, $method));
        }

        return $this->normaliseResult($controller->{$method}($request));
    }

    private function normaliseResult(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        if (is_array($result)) {
            return Response::json($result);
        }
        if (is_string($result) && $result !== '') {
            return Response::html($result);
        }
        return Response::noContent();
    }

    private function renderValidationFailure(Request $request, ValidationException $e): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'ok' => false,
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $e->firstError() ?? 'Validation failed',
                    'fields' => $e->errors(),
                ],
            ], 422);
        }

        Session::flashErrors($e->errors());
        Session::flashInput($request->all());

        return Response::redirect(safe_return_path($request->header('Referer')), 303);
    }

    private function renderBusinessFailure(Request $request, BusinessException $e): Response
    {
        if ($request->expectsJson()) {
            return Response::json([
                'ok' => false,
                'error' => ['code' => $e->errorCode, 'message' => $e->getMessage()],
            ], $e->status);
        }

        Session::flash('status', ['type' => 'error', 'message' => $e->getMessage()]);

        return Response::redirect(safe_return_path($request->header('Referer')), 303);
    }

    // -----------------------------------------------------------------------
    // URL compilation & reverse routing
    // -----------------------------------------------------------------------

    private function normaliseUri(string $uri): string
    {
        return '/' . trim(preg_replace('#/+#', '/', $uri) ?? '', '/');
    }

    private function compile(string $pattern): array
    {
        $paramNames = [];
        $regex = '';
        $offset = 0;

        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/', $pattern, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as $i => [$full, $at]) {
            $name = $matches[1][$i][0];
            $constraint = ($matches[2][$i][1] >= 0 && $matches[2][$i][0] !== '') ? $matches[2][$i][0] : '[^/]+';

            $regex .= preg_quote(substr($pattern, $offset, $at - $offset), '#');
            $regex .= '(?<' . $name . '>' . $constraint . ')';
            $paramNames[] = $name;
            $offset = $at + strlen($full);
        }

        $regex .= preg_quote(substr($pattern, $offset), '#');

        return ['#^' . $regex . '$#', $paramNames];
    }

    /**
     * Reverse routing: build a URL for a named route.
     *
     * @param array<string, string|int> $params
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->named[$name])) {
            throw new RouteNotFoundException('Unknown route name: ' . $name);
        }

        $pattern = $this->routes[$this->named[$name]]->pattern;

        $url = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::[^}]+)?\}/',
            static function (array $m) use (&$params): string {
                $key = $m[1];
                if (!array_key_exists($key, $params)) {
                    throw new \InvalidArgumentException("Missing route parameter [{$key}].");
                }
                $value = rawurlencode((string) $params[$key]);
                unset($params[$key]);
                return $value;
            },
            $pattern
        );

        if ($url === null) {
            throw new \RuntimeException('Unable to compile URL for route: ' . $name);
        }

        $query = http_build_query($params);
        return $query !== '' ? $url . '?' . $query : $url;
    }

    public static function currentRouteName(): ?string
    {
        return self::$currentRouteName;
    }
}

