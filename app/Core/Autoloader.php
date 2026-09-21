<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal PSR-4 autoloader so the application runs with or without `composer install`.
 */
final class Autoloader
{
    /** @var array<string, string> */
    private static array $prefixes = [];

    public static function register(): void
    {
        self::addNamespace('App\\', dirname(__DIR__));
        self::addNamespace('Tests\\', dirname(__DIR__, 2) . '/tests');

        spl_autoload_register(static function (string $class): void {
            foreach (self::$prefixes as $prefix => $baseDir) {
                if (!str_starts_with($class, $prefix)) {
                    continue;
                }
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . '/' . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        });
    }

    public static function addNamespace(string $prefix, string $baseDir): void
    {
        self::$prefixes[$prefix] = rtrim($baseDir, '/');
    }
}