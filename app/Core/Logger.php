<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Structured, rotating application logger. Never logs secrets.
 */
final class Logger
{
    private static string $path = '';
    private static string $minLevel = 'debug';
    private static int $maxBytes = 5242880; // 5 MB before rotation

    private const LEVELS = ['debug' => 1, 'info' => 2, 'notice' => 3, 'warning' => 4, 'error' => 5, 'critical' => 6];

    public static function boot(string $path, string $minLevel = 'debug'): void
    {
        self::$path = rtrim($path, '/');
        self::$minLevel = $minLevel;
        if (!is_dir(self::$path)) {
            @mkdir(self::$path, 0775, true);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $level = strtolower($level);
        if (self::weight($level) < self::weight(self::$minLevel)) {
            return;
        }

        $entry = [
            'ts' => gmdate('Y-m-d\TH:i:s\Z'),
            'level' => $level,
            'message' => $message,
            'context' => self::scrub($context),
        ];

        if (PHP_SAPI === 'cli') {
            $entry['cli'] = true;
        } else {
            $entry['request_id'] = RequestContext::id();
            $entry['path'] = $_SERVER['REQUEST_URI'] ?? null;
            $entry['ip'] = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line === false) {
            return;
        }

        if (self::$path === '') {
            error_log($line);
            return;
        }

        self::rotateIfNeeded();
        @file_put_contents(self::$path . '/app-' . gmdate('Y-m-d') . '.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function debug(string $m, array $c = []): void
    {
        self::log('debug', $m, $c);
    }

    public static function info(string $m, array $c = []): void
    {
        self::log('info', $m, $c);
    }

    public static function warning(string $m, array $c = []): void
    {
        self::log('warning', $m, $c);
    }

    public static function error(string $m, array $c = []): void
    {
        self::log('error', $m, $c);
    }

    public static function critical(string $m, array $c = []): void
    {
        self::log('critical', $m, $c);
    }

    private static function weight(string $level): int
    {
        return self::LEVELS[$level] ?? 2;
    }

    private static function rotateIfNeeded(): void
    {
        $file = self::$path . '/app-' . gmdate('Y-m-d') . '.log';
        if (is_file($file) && filesize($file) > self::$maxBytes) {
            @rename($file, $file . '.' . time());
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function scrub(array $context): array
    {
        $blocked = ['password', 'password_hash', 'pass', 'secret', 'token', 'api_key', 'authorization', 'card', 'cvv', 'pin'];
        foreach ($context as $key => $value) {
            $lower = strtolower((string) $key);
            foreach ($blocked as $needle) {
                if (str_contains($lower, $needle)) {
                    $context[$key] = '[redacted]';
                    continue 2;
                }
            }
            if (is_array($value)) {
                $context[$key] = self::scrub($value);
            } elseif (is_string($value) && mb_strlen($value) > 2000) {
                $context[$key] = mb_substr($value, 0, 2000) . '…';
            }
        }
        return $context;
    }
}