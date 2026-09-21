<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Lightweight filesystem cache with an optional APCu/Redis-free design.
 * Deliberately simple: shared hosting friendly, no daemons required.
 */
final class Cache
{
    private static string $path = '';

    public static function boot(string $path): void
    {
        self::$path = rtrim($path, '/');
        if (!is_dir(self::$path) && !@mkdir(self::$path, 0775, true) && !is_dir(self::$path)) {
            throw new RuntimeException('Cache directory is not writable: ' . self::$path);
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $file = self::file($key);
        if (!is_file($file)) {
            return $default;
        }

        $payload = @file_get_contents($file);
        if ($payload === false) {
            return $default;
        }

        $decoded = json_decode($payload, true);
        if (!is_array($decoded) || !array_key_exists('value', $decoded)) {
            @unlink($file);
            return $default;
        }

        $expires = $decoded['expires'] ?? null;
        if ($expires !== null && (int) $expires !== 0 && (int) $expires < time()) {
            @unlink($file);
            return $default;
        }

        return $decoded['value'];
    }

    public static function put(string $key, mixed $value, int $ttlSeconds = 3600): void
    {
        if (self::$path === '') {
            return;
        }
        $payload = json_encode([
            'value' => $value,
            'expires' => $ttlSeconds > 0 ? time() + $ttlSeconds : 0,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            return;
        }

        $file = self::file($key);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
            @rename($tmp, $file);
        }
    }

    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $sentinel = '__stayin_cache_miss__';
        $hit = self::get($key, $sentinel);
        if ($hit !== $sentinel) {
            return $hit;
        }
        $value = $callback();
        self::put($key, $value, $ttlSeconds);
        return $value;
    }

    public static function forget(string $key): void
    {
        @unlink(self::file($key));
    }

    public static function flush(): void
    {
        if (self::$path === '' || !is_dir(self::$path)) {
            return;
        }
        foreach ((array) glob(self::$path . '/*.cache') as $file) {
            @unlink((string) $file);
        }
    }

    public static function has(string $key): bool
    {
        return self::get($key, '__stayin_cache_miss__') !== '__stayin_cache_miss__';
    }

    private static function file(string $key): string
    {
        return self::$path . '/' . hash('sha256', $key) . '.cache';
    }
}
