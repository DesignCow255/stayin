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
        $regions = Property::regionsWithCounts(10);
        $featured = Property::featured(6);
        $popular = Property::published(12);
        $slides = HeroSlide::active();

        return $this->view('home', [
            'metaTitle' => config('seo.default_title'),
            'metaDescription' => config('seo.default_description'),
            'slides' => $slides,
            'regions' => $regions,
            'featured' => $featured,
            'popular' => $popular,
            'publishedCount' => Property::publishedCount(),
        ]);
    }
}
