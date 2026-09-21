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
            'count' => Property::publishedCount(),
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

        $result = \App\Repositories\SearchRepository::search(['region' => $region], $page);
        $properties = $result['properties'];

        return $this->view('stays/region', [
            'metaTitle' => 'Stays in ' . $region . ' · StayIn',
            'metaDescription' => 'Book hotels, lodges, villas and guest houses in ' . $region . ', Tanzania.',
            'region' => $region,
            'regionSlug' => $regionSlug,
            'count' => $result['count'],
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

        $propertyId = (int) $property['id'];
        $roomTypes = Property::activeRoomTypes($propertyId);
        $images = Property::images($propertyId);
        $reviews = Property::reviews($propertyId, 8);
        $similar = Property::similar($propertyId, (string) $property['region'], 3);

        // Host identity is real account data (first/last name, verification).
        $host = null;
        if (!empty($property['host_user_id'])) {
            $host = \App\Core\Database::first(
                'SELECT u.id, u.first_name, u.last_name, u.email_verified_at FROM users u WHERE u.id = ?',
                [(int) $property['host_user_id']]
            );
        }

        $selection = [
            'room_id' => $request->int('room_id', (int)($roomTypes[0]['id'] ?? 0)),
            'check_in' => $request->string('check_in'),
            'check_out' => $request->string('check_out'),
            'guests' => $request->int('guests', 1),
            'rooms' => $request->int('rooms', 1),
        ];
        $quote = null;
        $quoteError = null;
        if ($request->query('check_in') !== null || $request->query('check_out') !== null) {
            try {
                if (!in_array($selection['room_id'], array_map(static fn ($room) => (int)$room['id'], $roomTypes), true)) {
                    throw new \App\Core\BusinessException('Select a room at this property.');
                }
                $quote = \App\Services\BookingService::quote($selection['room_id'], $selection['check_in'], $selection['check_out'], $selection['guests'], $selection['rooms']);
            } catch (\App\Core\BusinessException $error) {
                $quoteError = $error->getMessage();
            }
        }

        return $this->view('stays/show', [
            'metaTitle' => $property['name'] . ' · StayIn',
            'metaDescription' => mb_substr(strip_tags((string) ($property['description_en'] ?? '')), 0, 155),
            'canonical' => url('/property/' . $property['slug']),
            'metaImage' => $images[0]['file_url'] ?? '',
            'property' => $property,
            'roomTypes' => $roomTypes,
            'images' => $images,
            'reviews' => $reviews,
            'similar' => $similar,
            'host' => $host,
            'selection' => $selection,
            'quote' => $quote,
            'quoteError' => $quoteError,
        ]);
    }
}
