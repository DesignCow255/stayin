<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Role/permission checks. Permissions come from config (authoritative),
 * roles from users.role + optional user_roles overrides.
 */
final class Gate
{
    /**
     * @param array<int, string>|null $roles
     */
    public static function allows(string $permission, ?array $roles = null): bool
    {
        $roles ??= AuthService::roles();

        if ($roles === []) {
            return false;
        }

        $granted = [];
        $map = (array) Config::get('auth.role_permissions', []);

        foreach ($roles as $role) {
            $permissions = $map[$role] ?? [];
            if (in_array('*', $permissions, true)) {
                return true;
            }
            $granted = [...$granted, ...$permissions];
        }

        return in_array($permission, $granted, true);
    }

    public static function denies(string $permission, ?array $roles = null): bool
    {
        return !self::allows($permission, $roles);
    }

    /**
     * @param array<int, string> $required
     * @param array<int, string>|null $roles
     */
    public static function anyRole(array $required, ?array $roles = null): bool
    {
        $roles ??= AuthService::roles();
        return count(array_intersect($required, $roles)) > 0;
    }
}
