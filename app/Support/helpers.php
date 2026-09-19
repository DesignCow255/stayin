<?php

declare(strict_types=1);

/**
 * Global view/controller helpers. Kept intentionally small.
 */

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

if (!function_exists('e')) {
    /**
     * Escape a value for HTML context. Every dynamic value in a view goes through this.
     */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            return htmlspecialchars((string) json_encode($value, JSON_UNESCAPED_UNICODE), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::get($key, $default);
    }
}

if (!function_exists('url')) {
    /**
     * Absolute application URL for a path.
     */
    function url(string $path = '/'): string
    {
        $base = rtrim(Config::string('app.url'), '/');
        if ($path === '' || $path === '/') {
            return $base . '/';
        }
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    /**
     * @param array<string, string|int> $params
     */
    function route(string $name, array $params = []): string
    {
        return App\Core\App::router()->url($name, $params);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $base = rtrim(Config::string('app.asset_url', Config::string('app.url')), '/');

        $file = Config::string('app.base_path') . '/public/' . $path;
        if (is_file($file)) {
            return $base . '/' . $path . '?v=' . substr((string) filemtime($file), -8);
        }

        return $base . '/' . $path;
    }
}

if (!function_exists('image_url')) {
    function image_url(?string $path, string $fallback = 'assets/images/placeholder-stay.svg'): string
    {
        if ($path === null || trim($path) === '') {
            return asset($fallback);
        }
        return url($path);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::token();
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve flashed form input after a failed submission.
     */
    function old(string $key, mixed $default = null): mixed
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Session::oldInput();
        }
        return $cache[$key] ?? $default;
    }
}

if (!function_exists('errors')) {
    /**
     * @return array<string, string>
     */
    function errors(): array
    {
        static $cache = null;
        if ($cache === null) {
            $cache = Session::errors();
        }
        return $cache;
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        return errors()[$field] ?? null;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}

if (!function_exists('current_request')) {
    function current_request(): ?Request
    {
        return App\Core\App::currentRequest();
    }
}

if (!function_exists('view_exists')) {
    function view_exists(string $view): bool
    {
        return View::exists($view);
    }
}