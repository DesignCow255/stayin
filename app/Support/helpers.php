<?php

declare(strict_types=1);

/**
 * Global view helpers. Kept intentionally small.
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

if (!function_exists('slugify')) {
    function slugify(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        return trim($value, '-');
    }
}

if (!function_exists('format_money')) {
    /**
     * Format a monetary amount for display. TZS is shown without decimals.
     */
    function format_money(float|int|string|null $amount, string $currency = 'TZS'): string
    {
        $amount = (float) ($amount ?? 0);
        $symbols = (array) Config::get('pricing.symbols', ['TZS' => 'Tshs.', 'USD' => '$']);

        $symbol = (string) ($symbols[$currency] ?? $currency);
        $decimals = $currency === 'TZS' ? 0 : 2;

        return $symbol . ' ' . number_format($amount, $decimals);
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

if (!function_exists('flash_status')) {
    /**
     * Pending one-shot status message (set by Router on business failures).
     *
     * @return array{type: string, message: string}|null
     */
    function flash_status(): ?array
    {
        $raw = Session::getFlash('status');
        if (is_array($raw) && isset($raw['message'])) {
            return ['type' => (string) ($raw['type'] ?? 'info'), 'message' => (string) $raw['message']];
        }
        if (is_string($raw) && $raw !== '') {
            return ['type' => 'info', 'message' => $raw];
        }
        return null;
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

if (!function_exists('app_locale')) {
    function app_locale(): string
    {
        return (string) ($GLOBALS['stayin_locale'] ?? Config::string('locale.default_locale', 'en'));
    }
}

if (!function_exists('auth_user')) {
    /**
     * @return array<string, mixed>|null
     */
    function auth_user(): ?array
    {
        return App\Services\AuthService::user();
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return App\Services\AuthService::check();
    }
}

function safe_return_path(?string $referer, string $fallback = '/'): string
{
    if (!$referer) return $fallback;
    $parts = parse_url($referer);
    if (!$parts || (isset($parts['host']) && $parts['host'] !== parse_url(config('app.url'), PHP_URL_HOST))) return $fallback;
    $path = $parts['path'] ?? '/';
    $base = rtrim((string) parse_url(config('app.url'), PHP_URL_PATH), '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) $path = substr($path, strlen($base));
    return str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path . (isset($parts['query']) ? '?' . $parts['query'] : '') : $fallback;
}
if (!function_exists('t')) {    
    function t(string $key): string
    {
        $locale = \App\Core\Session::get('locale', 'en');
        $file = config('app.base_path').'/resources/lang/'.$locale.'/ui.php';
        $words = is_file($file) ? require $file : [];
        return $words[$key] ?? $key;
    }
}

if (!function_exists('__')) {
    /**
     * Laravel-style translation with dotted keys, e.g. __('auth.login_title').
     * Falls back to the `t()` single-key lookup.
     */
    function __(string $key, array $replace = []): string
    {
        $locale = \App\Core\Session::get('locale', 'en');
        $file = config('app.base_path').'/resources/lang/'.$locale.'/ui.php';
        $lines = is_file($file) ? require $file : [];
        $result = $lines;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($result) || !array_key_exists($segment, $result)) {
                $result = $key; // fallback to the key itself
                break;
            }
            $result = $result[$segment];
        }
        $result = is_array($result) ? $key : (string) $result;
        foreach ($replace as $k => $v) {
            $result = str_replace(':' . $k, (string) $v, $result);
        }
        return $result;
    }
}
