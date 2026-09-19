<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Synchroniser-token CSRF protection for all state-changing browser requests.
 */
final class Csrf
{
    private const KEY = '_csrf_token';
    public const FIELD = '_token';
    public const HEADER = 'X-CSRF-TOKEN';

    public static function token(): string
    {
        Session::start();
        $token = Session::get(self::KEY);
        if (!is_string($token) || strlen($token) < 32) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }
        return $token;
    }

    public static function rotate(): void
    {
        Session::start();
        Session::put(self::KEY, bin2hex(random_bytes(32)));
    }

    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token, bool $rotateOnSuccess = false): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || $expected === '' || !is_string($token) || $token === '') {
            return false;
        }

        if (!hash_equals($expected, $token)) {
            Logger::warning('csrf.rejected', ['path' => $_SERVER['REQUEST_URI'] ?? null]);
            return false;
        }

        if ($rotateOnSuccess) {
            self::rotate();
        }

        return true;
    }

    public static function tokenFrom(Request $request): ?string
    {
        return $request->input(self::FIELD) !== null
            ? (string) $request->input(self::FIELD)
            : $request->header(self::HEADER);
    }
}