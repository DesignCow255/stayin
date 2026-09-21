<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Property;
use App\Core\Request;
use App\Core\Response;

final class ApiController extends Controller
{
    public function ping(): Response
    {
        return $this->json([
            'service' => 'stayin-api',
            'version' => 'v1',
            'time' => gmdate('c'),
        ]);
    }

    public function search(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $region = trim((string) $request->query('region', ''));

        $results = \App\Repositories\SearchRepository::search($request->query(), max(1,$request->int('page',1))); 

        return $this->json(['data' => $results]);
    }

    public function regions(): Response
    {
        return $this->json(['data' => Property::regionsWithCounts(50)]);
    }

    public function properties(Request $r): Response {
        return $this->json(\App\Repositories\SearchRepository::search($r->query(),max(1,$r->int('page',1))));
    }
    public function property(Request $r): Response {
        $p=Property::publishedBySlug((string)$r->routeParam('slug'));
        if(!$p) throw \App\Core\HttpException::notFound();
        $safe=array_intersect_key($p,array_flip(['id','name','slug','region','address','description_en','rating','review_count','amenities','house_rules','cancellation_policy']));
        return $this->json(['data'=>$safe,'rooms'=>Property::activeRoomTypes((int)$p['id'])]);
    }
    public function availability(Request $r): Response {
        return $this->json(['quote'=>\App\Services\BookingService::quote($r->int('room_id'),$r->string('check_in'),$r->string('check_out'),$r->int('guests',1),$r->int('rooms',1))]);
    }
}
