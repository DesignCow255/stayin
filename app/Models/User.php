<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Env;

/**
 * User entity/data access. Maps to the existing `users` table (preserved IDs).
 */
final class User
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM `users` WHERE `id` = ?', [$id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM `users` WHERE `email` = ?', [mb_strtolower(trim($email))]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByGoogleId(string $googleId): ?array
    {
        return Database::first('SELECT * FROM `users` WHERE `google_id` = ?', [$googleId]);
    }

    public static function emailExists(string $email): bool
    {
        return Database::count('SELECT COUNT(*) FROM `users` WHERE `email` = ?', [mb_strtolower(trim($email))]) > 0;
    }

    public static function phoneExists(string $phone): bool
    {
        return Database::count('SELECT COUNT(*) FROM `users` WHERE `phone` = ?', [trim($phone)]) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $now = gmdate('Y-m-d H:i:s');

        $defaults = [
            'uuid' => self::uuid(),
            'role' => 'guest',
            'preferred_language' => Env::string('APP_LOCALE', 'en') === 'sw' ? 'sw' : 'en',
            'preferred_currency' => Env::string('CURRENCY_DEFAULT_DISPLAY', 'TZS') === 'USD' ? 'USD' : 'TZS',
            'theme_preference' => 'system',
            'status' => 'active',
            'failed_logins' => 0,
            'created_at' => $now,
        ];

        $id = Database::insert('users', [...$defaults, ...$data]);

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        Database::update('users', $data, ['id' => $id]);
    }

    /**
     * @return array<int, string> role keys: explicit column plus any RBAC overrides.
     */
    public static function roles(array $user): array
    {
        $roles = [(string) ($user['role'] ?? 'guest')];

        // Optional RBAC overrides. The legacy users.role column remains
        // authoritative when the RBAC tables have not been installed.
        $hasRoles = (bool) Database::scalar(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = 'roles'"
        );

        $hasUserRoles = (bool) Database::scalar(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE()
             AND table_name = 'user_roles'"
        );

        if ($hasRoles && $hasUserRoles) {
            $extra = Database::select(
                'SELECT r.`key`
                 FROM `user_roles` ur
                 JOIN `roles` r ON r.`id` = ur.`role_id`
                 WHERE ur.`user_id` = ?',
                [(int) $user['id']]
            );

            foreach ($extra as $row) {
                $roles[] = (string) $row['key'];
            }
        }

        return array_values(array_unique($roles));
    }

    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Full name convenience for views.
     *
     * @param array<string, mixed> $user
     */
    public static function displayName(array $user): string
    {
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        return $name !== '' ? $name : (string) ($user['email'] ?? 'Guest');
    }
}
