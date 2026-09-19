<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Property entity/data access (read paths for public discovery).
 */
final class Property
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function published(int $limit = 12, int $offset = 0): array
    {
        return Database::select(
            'SELECT p.`id`, p.`uuid`, p.`name`, p.`slug`, p.`region`, p.`district`, p.`address`,
                    p.`property_type`, p.`rating`, p.`review_count`, p.`featured`,
                    (SELECT `file_url` FROM `property_images` pi WHERE pi.`property_id` = p.`id`
                        ORDER BY pi.`is_primary` DESC, pi.`sort_order` ASC LIMIT 1) AS cover_image,
                    (SELECT MIN(`base_price`) FROM `room_types` rt WHERE rt.`property_id` = p.`id`
                        AND rt.`status` = \'active\') AS min_price,
                    (SELECT `currency` FROM `room_types` rt2 WHERE rt2.`property_id` = p.`id`
                        AND rt2.`status` = \'active\' ORDER BY rt2.`base_price` ASC LIMIT 1) AS currency
             FROM `properties` p
             WHERE p.`status` = \'published\'
             ORDER BY p.`featured` DESC, p.`rating` DESC, p.`id` ASC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function featured(int $limit = 6): array
    {
        return Database::select(
            'SELECT p.`id`, p.`name`, p.`slug`, p.`region`, p.`rating`, p.`review_count`,
                    (SELECT `file_url` FROM `property_images` pi WHERE pi.`property_id` = p.`id`
                        ORDER BY pi.`is_primary` DESC, pi.`sort_order` ASC LIMIT 1) AS cover_image,
                    (SELECT MIN(`base_price`) FROM `room_types` rt WHERE rt.`property_id` = p.`id`
                        AND rt.`status` = \'active\') AS min_price,
                    (SELECT currency FROM room_types rt WHERE rt.property_id=p.id AND rt.status='active' ORDER BY rt.base_price LIMIT 1) AS currency
             FROM `properties` p
             WHERE p.`status` = \'published\' AND p.`featured` = 1
             ORDER BY p.`rating` DESC
             LIMIT ?',
            [$limit]
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function publishedBySlug(string $slug): ?array
    {
        return Database::first(
            'SELECT p.*, u.`first_name`, u.`last_name`, u.`id` AS host_user_id
             FROM `properties` p
             JOIN `users` u ON u.`id` = p.`host_id`
             WHERE p.`slug` = ? AND p.`status` = \'published\'',
            [$slug]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function activeRoomTypes(int $propertyId): array
    {
        return Database::select(
            'SELECT * FROM `room_types` WHERE `property_id` = ? AND `status` = \'active\' ORDER BY `base_price` ASC',
            [$propertyId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function images(int $propertyId): array
    {
        return Database::select(
            'SELECT * FROM `property_images` WHERE `property_id` = ? ORDER BY `is_primary` DESC, `sort_order` ASC',
            [$propertyId]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function reviews(int $propertyId, int $limit = 10): array
    {
        return Database::select(
            'SELECT r.*, u.`first_name`, u.`last_name`
             FROM `reviews` r
             JOIN `users` u ON u.`id` = r.`guest_id`
             WHERE r.`property_id` = ? AND r.`status` = \'published\'
             ORDER BY r.`created_at` DESC
             LIMIT ?',
            [$propertyId, $limit]
        );
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function regionsWithCounts(int $limit = 12): array
    {
        return Database::select(
            'SELECT `region`, COUNT(*) AS `count`
             FROM `properties`
             WHERE `status` = \'published\' AND `region` IS NOT NULL AND `region` <> \'\'
             GROUP BY `region`
             ORDER BY `count` DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function publishedCount(): int
    {
        return Database::count('SELECT COUNT(*) FROM `properties` WHERE `status` = \'published\'');
    }

    /**
     * Simple server-side search for published properties.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, ?string $region = null, int $limit = 24, int $offset = 0): array
    {
        $conditions = ['p.`status` = \'published\''];
        $params = [];

        if ($query !== '') {
            $conditions[] = '(p.`name` LIKE ? OR p.`region` LIKE ?)';
            $params[] = '%' . $query . '%';
            $params[] = '%' . $query . '%';
        }
        if ($region !== null && $region !== '') {
            $conditions[] = 'p.`region` = ?';
            $params[] = $region;
        }

        $params[] = $limit;
        $params[] = $offset;

        return Database::select(
            'SELECT p.`id`, p.`name`, p.`slug`, p.`region`, p.`property_type`, p.`rating`, p.`review_count`,
                    (SELECT `file_url` FROM `property_images` pi WHERE pi.`property_id` = p.`id`
                        ORDER BY pi.`is_primary` DESC, pi.`sort_order` ASC LIMIT 1) AS cover_image,
                    (SELECT MIN(`base_price`) FROM `room_types` rt WHERE rt.`property_id` = p.`id`
                        AND rt.`status` = \'active\') AS min_price,
                    (SELECT currency FROM room_types rt WHERE rt.property_id=p.id AND rt.status='active' ORDER BY rt.base_price LIMIT 1) AS currency
             FROM `properties` p
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY p.`featured` DESC, p.`rating` DESC
             LIMIT ? OFFSET ?',
            $params
        );
    }
}
