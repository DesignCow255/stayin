<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\HeroSlide;
use App\Models\Property;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $featured = Property::featured(8);
        $recent = Property::recentlyAdded(6);
        $topRated = Property::topRated(3);
        $regions = Property::regionCovers(9);
        $typeCounts = Property::typeCounts();

        // Hero slideshow: curated hero_slides (promo / ad / service / announcement).
        $slides = \App\Models\HeroSlide::active();

        // Hero photography is always a real published listing photograph.
        $hero = $featured[0] ?? $recent[0] ?? null;
        $heroImage = null;
        if ($hero !== null) {
            $heroImage = \App\Core\Database::scalar(
                'SELECT file_url FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, sort_order ASC LIMIT 1',
                [(int) $hero['id']]
            );
        }

        return $this->view('home', [
            'metaTitle' => config('seo.default_title'),
            'metaDescription' => config('seo.default_description'),
            'regions' => $regions,
            'featured' => $featured,
            'recent' => $recent,
            'topRated' => $topRated,
            'typeCounts' => $typeCounts,
            'publishedCount' => Property::publishedCount(),
            'slides' => $slides,
            'heroImage' => $heroImage,
            'heroProperty' => $hero,
        ]);
    }
}
