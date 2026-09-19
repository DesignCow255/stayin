<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-request correlation id used in logs, error pages and health output.
 */
final class RequestContext
{
    private static ?string $id = null;
    private static float $startedAt = 0.0;

    public static function boot(): void
    {
        self::$startedAt = microtime(true);
        self::$id = self::generate();
    }

    public static function id(): string
    {
        return self::$id ??= self::generate();
    }

    public static function startedAt(): float
    {
        return self::$startedAt ?: microtime(true);
    }

    public static function elapsedMs(): float
    {
        return round((microtime(true) - self::startedAt()) * 1000, 2);
    }

    private static function generate(): string
    {
        return strtoupper(bin2hex(random_bytes(8)));
    }
}