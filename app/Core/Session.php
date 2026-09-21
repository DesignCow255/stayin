<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session bootstrap with secure cookie defaults and flash messaging.
 */
final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || PHP_SAPI === 'cli') {
            self::$started = true;
            if (!isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $lifetime = Config::int('app.session_lifetime_minutes', 120) * 60;
        $secure = Config::bool('app.session_secure', false);

        session_name(Config::string('app.session_name', 'stayin_session'));
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.cookie_httponly', '1');

        $savePath = Config::string('app.session_path', '');
        if ($savePath !== '' && is_dir($savePath)) {
            session_save_path($savePath);
        }

        session_start();
        self::$started = true;

        self::enforceIdleTimeout($lifetime);
    }

    private static function enforceIdleTimeout(int $lifetime): void
    {
        $last = $_SESSION['_last_activity'] ?? null;
        if (is_int($last) && (time() - $last) > $lifetime) {
            self::flush();
            self::regenerate();
            self::flash('status', 'Your session expired for security reasons. Please sign in again.');
        }
        $_SESSION['_last_activity'] = time();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }

    public static function forget(string ...$keys): void
    {
        self::start();
        foreach ($keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    public static function regenerate(bool $deleteOld = true): void
    {
        if (PHP_SAPI === 'cli' || session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        session_regenerate_id($deleteOld);
    }

    public static function flash(string $key, mixed $value): void
    {
        self::start();
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        self::start();
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    /**
     * Keep old input for form re-rendering.
     *
     * @param array<string, mixed> $input
     */
    public static function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token'], $input['_method']);
        self::flash('_old', $input);
    }

    public static function oldInput(): array
    {
        $old = self::getFlash('_old', []);
        return is_array($old) ? $old : [];
    }

    /**
     * @param array<string, string> $errors
     */
    public static function flashErrors(array $errors): void
    {
        self::flash('_errors', $errors);
    }

    public static function errors(): array
    {
        $errors = self::getFlash('_errors', []);
        return is_array($errors) ? $errors : [];
    }

    public static function flush(): void
    {
        self::start();
        $_SESSION = [];
    }

    public static function destroy(): void
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => 'Lax',
                ]);
            }
            session_destroy();
        }
        self::$started = false;
    }
}