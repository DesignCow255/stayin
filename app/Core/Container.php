<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Application container: a deliberately small service locator.
 *
 * It is NOT a full DI framework — it provides singletons for shared services,
 * and resolves controller/middleware classes on demand.
 */
final class Container
{
    /** @var array<string, mixed> */
    private array $bindings = [];
    /** @var array<string, mixed> */
    private array $instances = [];

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public static function setInstance(self $container): void
    {
        self::$instance = $container;
    }

    /**
     * Static accessor used by the router/pipeline hot path. Middleware aliases
     * (e.g. "csrf", "auth", "throttle:api") are resolved through config.
     */
    public static function get(string $abstract): mixed
    {
        $container = self::instance();

        if (!class_exists($abstract) && !isset($container->bindings[$abstract])) {
            [$alias, $param] = array_pad(explode(':', $abstract, 2), 2, null);
            $aliases = (array) Config::get('middleware.aliases', []);

            if (isset($aliases[$alias])) {
                $class = $aliases[$alias];
                // Carry the parameter (e.g. throttle:api) on the instance for
                // middleware that needs it.
                if ($param !== null) {
                    return $container->instances['alias:' . $abstract] ??= $container->make($class);
                }
                return $container->make($class);
            }
        }

        return $container->make($abstract);
    }

    /**
     * Static singleton registration used at boot time.
     */
    public static function singleton(string $abstract, callable|string $concrete): void
    {
        self::instance()->bind($abstract, $concrete, true);
    }

    /**
     * @param callable(self):mixed|class-string $concrete
     */
    public function bind(string $abstract, callable|string $concrete, bool $shared = true): void
    {
        $this->bindings[$abstract] = ['concrete' => $concrete, 'shared' => $shared];
        unset($this->instances[$abstract]);
    }

    /**
     * Register a pre-built instance (object already constructed).
     */
    public function set(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function make(string $abstract): mixed
    {
        if (array_key_exists($abstract, $this->instances)) {
            return $this->instances[$abstract];
        }

        $binding = $this->bindings[$abstract] ?? null;

        if ($binding === null) {
            if (!class_exists($abstract)) {
                throw new RuntimeException('Cannot resolve ' . $abstract . ' — no binding and no class.');
            }
            $object = new $abstract();
        } else {
            $concrete = $binding['concrete'];
            $object = is_callable($concrete) ? $concrete($this) : $this->build((string) $concrete);
        }

        if ($binding === null || $binding['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Resolve a class, auto-injecting constructor dependencies by type and name.
     */
    public function build(string $class): object
    {
        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException($class . ' is not instantiable.');
        }

        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
                continue;
            }

            if (array_key_exists($name, $this->instances)) {
                $arguments[] = $this->instances[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf('Cannot resolve parameter $%s of %s::__construct().', $name, $class)
            );
        }

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Invoke a callable or controller action, resolving parameters.
     *
     * @param array<string, string> $routeParams
     */
    public function call(callable|array $callable, array $routeParams = []): mixed
    {
        if (is_array($callable) && is_string($callable[0])) {
            $callable = [$this->make($callable[0]), $callable[1]];
        }

        $reflection = is_array($callable)
            ? new \ReflectionMethod($callable[0], $callable[1])
            : new \ReflectionFunction(\Closure::fromCallable($callable));

        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $class = $type->getName();
                if ($class === Request::class && isset($this->instances[Request::class])) {
                    $arguments[] = $this->instances[Request::class];
                    continue;
                }
                $arguments[] = $this->make($class);
                continue;
            }

            if (array_key_exists($name, $routeParams)) {
                $arguments[] = $routeParams[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $arguments[] = null;
        }

        return $reflection->invokeArgs(is_array($callable) ? $callable[0] : null, $arguments);
    }
}