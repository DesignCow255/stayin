<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Immutable-ish configuration repository with dot notation access.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    /** @var array<string, bool> */
    private static array $loaded = [];

    public static function load(string $name, string $path): void
    {
        if (isset(self::$loaded[$name])) {
            return;
        }
        self::$loaded[$name] = true;

        if (!is_file($path)) {
            self::$items[$name] = [];
            return;
        }

        /** @var mixed $data */
        $data = require $path;
        self::$items[$name] = is_array($data) ? $data : [];
    }

    public static function set(string $key, mixed $value): void
    {
        $cursor =& self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) $cursor[$segment] = [];
            $cursor =& $cursor[$segment];
        }
        $cursor = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!str_contains($key, '.')) {
            return self::$items[$key] ?? $default;
        }

        $segments = explode('.', $key);
        $cursor = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    public static function string(string $key, string $default = ''): string
    {
        $v = self::get($key, $default);
        return is_scalar($v) ? (string) $v : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key, $default);
        if (is_bool($v)) {
            return $v;
        }
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<mixed>
     */
    public static function array(string $key, array $default = []): array
    {
        $v = self::get($key, $default);
        return is_array($v) ? $v : $default;
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__stayin_missing__') !== '__stayin_missing__';
    }

    public static function all(): array
    {
        return self::$items;
    }
}
