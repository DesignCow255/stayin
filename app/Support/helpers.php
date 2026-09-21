<?php

declare(strict_types=1);

// --- Explicitly require the Config class ---
// This ensures Config is loaded before the config() helper function is called.
// --- End explicit require ---

/**
 * Global view helpers. Kept intentionally small.
 */

use App\Core\Cache;
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
        // The Config class is now explicitly required at the top of this file.
        return \App\Core\Config::get($key, $default);
    }
}

if (!function_exists('icon')) {
    /**
     * Render an inline SVG icon from the self-hosted registry.
     *
     * @see \App\Support\Icon
     */
    function icon(string $name, string $class = '', ?string $label = null): string
    {
        return \App\Support\Icon::render($name, $class, $label);
    }
}

if (!function_exists('favourite_ids')) {
    /**
     * Property IDs the signed-in user has saved. Resolved once per request so
     * listing pages never fire a query per card.
     *
     * @return array<int, bool> keyed by property id for O(1) lookup
     */
    function favourite_ids(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        $userId = \App\Services\AuthService::id();

        if ($userId !== null) {
            foreach (\App\Core\Database::select('SELECT property_id FROM favourites WHERE user_id = ?', [$userId]) as $row) {
                $cache[(int) $row['property_id']] = true;
            }
        }

        return $cache;
    }
}

if (!function_exists('is_favourite')) {
    function is_favourite(int $propertyId): bool
    {
        return isset(favourite_ids()[$propertyId]);
    }
}

if (!function_exists('amenity_items')) {
    /**
     * Normalise `properties.amenities` (JSON column) into usable items.
     *
     * @return array<int, array{label: string, icon: string}>
     */
    function amenity_items(mixed $amenities): array
    {
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true);
        }
        if (!is_array($amenities)) {
            return [];
        }

        $items = [];
        foreach ($amenities as $amenity) {
            if (!is_scalar($amenity)) {
                continue;
            }
            $label = \App\Support\Icon::label((string) $amenity);
            $items[] = ['label' => $label, 'icon' => \App\Support\Icon::forAmenity($label)];
        }

        return $items;
    }
}

if (!function_exists('rating_stars')) {
    /**
     * Star markup for a real rating value. Never renders a rating that is absent.
     */
    function rating_stars(float|int|string|null $rating, string $class = 'rating'): string
    {
        $value = (float) ($rating ?? 0);
        if ($value <= 0) {
            return '';
        }

        $rounded = (int) round($value);
        $html = '<span class="' . e($class) . '">';
        for ($i = 1; $i <= 5; $i++) {
            $html .= icon($i <= $rounded ? 'star-filled' : 'star', $class . '__star');
        }
        $html .= '</span>';

        return $html;
    }
}

if (!function_exists('format_date')) {
    /**
     * Locale-aware short date from a stored `Y-m-d` / datetime string.
     */
    function format_date(?string $value, string $format = 'j M Y'): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return (string) $value;
        }

        return date($format, $timestamp);
    }
}

if (!function_exists('region_url')) {
    function region_url(string $region): string
    {
        return url('/stays/' . slugify($region));
    }
}

if (!function_exists('track')) {
    /**
     * `data-analytics-*` attributes for a conversion component (§29).
     *
     * @param array<string, string|int|float|null> $params
     */
    function track(string $event, array $params = []): string
    {
        return \App\Support\Analytics::attrs($event, $params);
    }
}

if (!function_exists('property_type_label')) {
    function property_type_label(string $value): string
    {
        return \App\Support\PropertyType::label($value);
    }
}

if (!function_exists('property_type_plural')) {
    function property_type_plural(string $value): string
    {
        return \App\Support\PropertyType::plural($value);
    }
}

if (!function_exists('active_filter_count')) {
    /**
     * Number of filters actively narrowing a search — drives the "Show X
     * properties" affordance and the filter badge.
     *
     * @param array<string, mixed> $filters
     */
    function active_filter_count(array $filters): int
    {
        $ignored = ['page', 'sort', 'currency'];
        $count = 0;

        foreach ($filters as $key => $value) {
            if (in_array($key, $ignored, true)) {
                continue;
            }
            if (is_array($value) ? $value !== [] : trim((string) $value) !== '') {
                $count++;
            }
        }

        return $count;
    }
}

if (!function_exists('url')) {
    /**
     * Absolute application URL for a path.
     */
    function url(string $path = '/'): string
    {
        $base = rtrim(\App\Core\Config::string('app.url'), '/');
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
        $base = rtrim(\App\Core\Config::string('app.asset_url', \App\Core\Config::string('app.url')), '/');

        $file = \App\Core\Config::string('app.base_path') . '/public/' . $path;
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
        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }
        $relativePath = ltrim($path, '/');
        $publicFile = \App\Core\Config::string('app.base_path') . '/public/' . $relativePath;
        if (is_file($publicFile)) {
            return asset($relativePath);
        }
        return url($path);
    }
}

if (!function_exists('stayin_image_asset')) {
    /**
     * Local Tanzanian/African visual fallback set for generated/demo imagery.
     *
     * Real uploaded property photographs still win; these are used only when a
     * listing/region/hero image is absent or callers explicitly need a local
     * editorial visual. Keeping the set local avoids hotlinked stock images and
     * keeps releases deterministic/offline-safe.
     */
    function stayin_image_asset(?string $context = null): string
    {
        $images = [
            'hero' => 'assets/images/stays/tanzania-hero.svg',
            'zanzibar' => 'assets/images/stays/zanzibar-villa.svg',
            'serengeti' => 'assets/images/stays/serengeti-safari-lodge.svg',
            'kilimanjaro' => 'assets/images/stays/kilimanjaro-cabin.svg',
            'dar' => 'assets/images/stays/dar-es-salaam-apartment.svg',
            'dar es salaam' => 'assets/images/stays/dar-es-salaam-apartment.svg',
            'arusha' => 'assets/images/stays/arusha-garden-hotel.svg',
            'mwanza' => 'assets/images/stays/lake-victoria-guesthouse.svg',
            'lake' => 'assets/images/stays/lake-victoria-guesthouse.svg',
            'villa' => 'assets/images/stays/zanzibar-villa.svg',
            'apartment' => 'assets/images/stays/dar-es-salaam-apartment.svg',
            'hotel' => 'assets/images/stays/arusha-garden-hotel.svg',
            'lodge' => 'assets/images/stays/serengeti-safari-lodge.svg',
            'cabin' => 'assets/images/stays/kilimanjaro-cabin.svg',
            'guesthouse' => 'assets/images/stays/lake-victoria-guesthouse.svg',
            'default' => 'assets/images/placeholder-stay.svg',
        ];

        $needle = mb_strtolower(trim((string) $context));
        if ($needle !== '') {
            foreach ($images as $key => $path) {
                if ($key !== 'default' && str_contains($needle, $key)) {
                    return $path;
                }
            }

            $pool = array_values(array_diff_key($images, ['default' => true, 'hero' => true]));
            return $pool[abs(crc32($needle)) % count($pool)];
        }

        return $images['default'];
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
        $symbols = (array) \App\Core\Config::get('pricing.symbols', ['TZS' => 'Tshs.', 'USD' => '$']);

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
     * Retrieve flashed form input after a failed validation.
     */
    function old(string $key, mixed $default = null): mixed
    {
        static $old = null;
        if ($old === null) {
            $old = Session::getFlash('_old', []);
            if (!is_array($old)) {
                $old = [];
            }
        }
        return array_key_exists($key, $old) ? $old[$key] : $default;
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
        return (string) ($GLOBALS['stayin_locale'] ?? \App\Core\Config::string('locale.default_locale', 'en'));
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
    if (!$parts || (isset($parts['host']) && $parts['host'] !== parse_url(\App\Core\Config::string('app.url'), PHP_URL_HOST))) return $fallback;
    $path = $parts['path'] ?? '/';
    $base = rtrim((string) parse_url(\App\Core\Config::string('app.url'), PHP_URL_PATH), '/');
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
