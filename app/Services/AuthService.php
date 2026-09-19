<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use App\Core\Session;
use App\Models\User;

/**
 * Session-based authentication with login throttling and lockout.
 * Passwords are hashed with Argon2id (bcrypt fallback) — never custom crypto.
 */
final class AuthService
{
    private const SESSION_KEY = 'auth_user_id';

    /**
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        $id = Session::get(self::SESSION_KEY);
        if (!is_int($id) && !is_string($id)) {
            return null;
        }

        $user = User::find((int) $id);
        if ($user === null || ($user['status'] ?? '') !== 'active') {
            Session::forget(self::SESSION_KEY);
            return $user = null;
        }

        return $user;
    }

    public static function anyRole(array $roles): bool { return count(array_intersect($roles, self::roles())) > 0; }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user === null ? null : (int) $user['id'];
    }

    /**
     * @return array<int, string>
     */
    public static function roles(): array
    {
        $user = self::user();
        return $user === null ? [] : User::roles($user);
    }

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $email = mb_strtolower(trim($email));

        if (self::isLockedOut($email)) {
            return false;
        }

        $user = User::findByEmail($email);

        if ($user === null || ($user['password_hash'] ?? null) === null
            || !password_verify($password, (string) $user['password_hash'])
        ) {
            self::recordFailure($email, $user);
            return false;
        }

        if (($user['status'] ?? '') !== 'active') {
            Logger::warning('auth.login_blocked_status', ['user_id' => (int) $user['id'], 'status' => $user['status']]);
            return false;
        }

        // Transparent rehash when the algorithm/cost has changed.
        if (password_needs_rehash((string) $user['password_hash'], self::algo(), self::options())) {
            User::update((int) $user['id'], ['password_hash' => password_hash($password, self::algo(), self::options())]);
        }

        self::login((int) $user['id'], $remember);
        self::clearFailures($email);

        Logger::info('auth.login', ['user_id' => (int) $user['id']]);

        return true;
    }

    public static function login(int $userId, bool $remember = false): void
    {
        Session::start();
        Session::regenerate(true);
        Session::put(self::SESSION_KEY, $userId);
        User::update($userId, ['last_login_at' => gmdate('Y-m-d H:i:s')]);
    }

    public static function logout(): void
    {
        Session::start();
        Session::forget(self::SESSION_KEY);
        Session::regenerate(true);
    }

    private static function isLockedOut(string $email): bool
    {
        $row = Database::first(
            'SELECT `locked_until` FROM `users` WHERE `email` = ?',
            [$email]
        );

        if ($row === null || $row['locked_until'] === null) {
            return false;
        }

        return strtotime((string) $row['locked_until']) > time();
    }

    private static function recordFailure(string $email, ?array $user): void
    {
        $max = Config::int('auth.max_login_attempts', 5);
        $lockMinutes = Config::int('auth.lockout_minutes', 15);

        if ($user === null) {
            return; // Do not reveal or track unknown emails beyond logging.
        }

        $attempts = (int) $user['failed_logins'] + 1;
        $data = ['failed_logins' => $attempts];

        if ($attempts >= $max) {
            $data['locked_until'] = gmdate('Y-m-d H:i:s', time() + $lockMinutes * 60);
            $data['failed_logins'] = 0;
            Logger::warning('auth.lockout', ['user_id' => (int) $user['id']]);
        }

        User::update((int) $user['id'], $data);
    }

    private static function clearFailures(string $email): void
    {
        Database::update('users', ['failed_logins' => 0, 'locked_until' => null], ['email' => $email]);
    }

    public static function algo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /**
     * @return array<string, int>
     */
    public static function options(): array
    {
        return (array) Config::get('auth.hash_options', ['cost' => 12]);
    }

    public static function hash(string $password): string
    {
        return password_hash($password, self::algo(), self::options());
    }
}

