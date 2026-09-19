<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;

/**
 * Internal helper for sitemap URL sources.
 */
final class DatabaseSitemap
{
    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    public static function propertyUrls(): array
    {
        $rows = Database::select(
            'SELECT `slug`, `updated_at` FROM `properties`
             WHERE `status` = \'published\'
             ORDER BY `id` ASC
             LIMIT 20000'
        );

        $urls = [];
        foreach ($rows as $row) {
            $urls[] = [
                'loc' => url('/property/' . $row['slug']),
                'lastmod' => gmdate('Y-m-d', strtotime((string) $row['updated_at'])),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        return $urls;
    }
}
