<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal, dependency-free .env loader.
 *
 * - Never overwrites variables already present in the real environment.
 * - Supports quoted values, inline "#" comments (outside quotes) and escapes.
 * - Never throws on a malformed line; malformed lines are skipped silently.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(string $file): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        if (!is_file($file) || !is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            if ($key === '' || preg_match('/[^A-Za-z0-9_.]/', $key)) {
                continue;
            }

            $value = self::normaliseValue($value);

            // Real environment wins; then pre-existing $_ENV / $_SERVER / getenv().
            if (array_key_exists($key, $_ENV) || array_key_exists($key, $_SERVER) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }

    private static function normaliseValue(string $value): string
    {
        $value = trim($value);
        $len = strlen($value);

        if ($len >= 2) {
            $first = $value[0];
            $last = $value[$len - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $inner = substr($value, 1, -1);
                if ($first === '"') {
                    $inner = str_replace(['\\n', '\\r', '\\t', '\\"', '\\\\'], ["\n", "\r", "\t", '"', '\\'], $inner);
                }
                return $inner;
            }
        }

        // Strip an unquoted trailing comment.
        if (str_contains($value, '#')) {
            $value = rtrim(substr($value, 0, (int) strpos($value, '#')), " \t");
        }

        return $value;
    }

    /**
     * Read a raw environment value with type coercion for booleans / null.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $raw = $_ENV[$key] ?? $_SERVER[$key] ?? (getenv($key) !== false ? getenv($key) : null);

        if ($raw === null || $raw === '') {
            return $default;
        }

        return match (strtolower((string) $raw)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $raw,
        };
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key, $default);
        return is_bool($v) ? $v : in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key, $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $v = self::get($key, $default);
        return is_scalar($v) ? (string) $v : $default;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }
}
