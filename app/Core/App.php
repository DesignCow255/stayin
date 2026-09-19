<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small service container / application registry. Deliberately not a framework.
 */
final class App
{
    /** @var array<string, mixed> */
    private static array $instances = [];
    /** @var array<string, callable> */
    private static array $factories = [];
    private static ?Router $router = null;
    private static ?Request $request = null;

    public static function singleton(string $id, callable $factory): void
    {
        self::$factories[$id] = $factory;
    }

    public static function instance(string $id, mixed $instance): void
    {
        self::$instances[$id] = $instance;
    }

    public static function make(string $id): mixed
    {
        if (array_key_exists($id, self::$instances)) {
            return self::$instances[$id];
        }

        if (isset(self::$factories[$id])) {
            return self::$instances[$id] = (self::$factories[$id])(self::class);
        }

        if (class_exists($id)) {
            return self::$instances[$id] = new $id();
        }

        throw new \RuntimeException('Service not found in container: ' . $id);
    }

    public static function has(string $id): bool
    {
        return array_key_exists($id, self::$instances) || isset(self::$factories[$id]);
    }

    public static function setRouter(Router $router): void
    {
        self::$router = $router;
    }

    public static function router(): Router
    {
        return self::$router ??= new Router(Config::string('app.base_path'));
    }

    public static function setCurrentRequest(Request $request): void
    {
        self::$request = $request;
    }

    public static function currentRequest(): ?Request
    {
        return self::$request;
    }

    /**
     * Resolve a class from the container or construct it.
     */
    public static function resolve(string $class): object
    {
        if (array_key_exists($class, self::$instances)) {
            return self::$instances[$class];
        }

        $instance = self::make($class);
        if (!is_object($instance)) {
            throw new \RuntimeException('Container returned a non-object for ' . $class);
        }

        return $instance;
    }

    public static function flush(): void
    {
        self::$instances = [];
    }
}