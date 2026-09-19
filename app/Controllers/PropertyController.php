<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Models\Property;

final class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $page = max(1, $request->int('page', 1));
        $perPage = 24;

        $properties = Property::published($perPage, ($page - 1) * $perPage);

        return $this->view('stays/index', [
            'metaTitle' => 'All stays · StayIn',
            'metaDescription' => 'Browse every published stay on StayIn across Tanzania.',
            'properties' => $properties,
            'page' => $page,
        ]);
    }

    public function byRegion(Request $request): Response
    {
        $regionSlug = (string) $request->routeParam('region', '');
        $region = str_replace('-', ' ', $regionSlug);
        $region = mb_strtoupper(mb_substr($region, 0, 1)) . mb_substr($region, 1);

        $page = max(1, $request->int('page', 1));
        $perPage = 24;

        $properties = Property::search('', $region, $perPage, ($page - 1) * $perPage);

        return $this->view('stays/region', [
            'metaTitle' => 'Stays in ' . $region . ' · StayIn',
            'metaDescription' => 'Book hotels, lodges, villas and guest houses in ' . $region . ', Tanzania.',
            'region' => $region,
            'regionSlug' => $regionSlug,
            'properties' => $properties,
            'page' => $page,
        ]);
    }

    public function show(Request $request): Response
    {
        $slug = (string) $request->routeParam('slug', '');

        $property = Property::publishedBySlug($slug);

        if ($property === null) {
            return $this->errorPage(404, 'This property is not available.');
        }

        $roomTypes = Property::activeRoomTypes((int) $property['id']);
        $images = Property::images((int) $property['id']);
        $reviews = Property::reviews((int) $property['id'], 8);

        return $this->view('stays/show', [
            'metaTitle' => $property['name'] . ' · StayIn',
            'metaDescription' => mb_substr(strip_tags((string) ($property['description_en'] ?? '')), 0, 155),
            'property' => $property,
            'roomTypes' => $roomTypes,
            'images' => $images,
            'reviews' => $reviews,
        ]);
    }
}
