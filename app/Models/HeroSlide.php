<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * CMS hero slides for the homepage carousel.
 */
final class HeroSlide
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function active(): array
    {
        return Database::select(
            'SELECT `id`, `title_en`, `title_sw`, `description_en`, `description_sw`, `image_url`,
                    `cta_text_en`, `cta_text_sw`, `cta_link`, `slide_type`, `display_order`
             FROM `hero_slides`
             WHERE `is_active` = 1
               AND (starts_at IS NULL OR starts_at <= NOW())
               AND (ends_at IS NULL OR ends_at >= NOW())
             ORDER BY `display_order` ASC
             LIMIT 10'
        );
    }
}
