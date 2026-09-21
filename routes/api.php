<?php

/**
 * JSON API routes (versioned).
 *
 * @var App\Core\Router $router
 */

$router->group(['prefix' => '/api/v1'], static function (App\Core\Router $router): void {
    $router->get('/ping', 'ApiController@ping', 'api.ping');
    $router->get('/search', 'ApiController@search', 'api.search')->middleware(['throttle:search']);
    $router->get('/properties', 'ApiController@properties', 'api.properties')->middleware(['throttle:api']);
    $router->get('/properties/{slug}', 'ApiController@property', 'api.property.show')->middleware(['throttle:api']);
    $router->get('/availability', 'ApiController@availability', 'api.availability')->middleware(['throttle:api']);
    $router->post('/availability/hold', 'BookingController@hold', 'api.hold.create')->middleware(['auth','throttle:hold_create']);
    $router->get('/regions', 'ApiController@regions', 'api.regions')->middleware(['throttle:api']);
});
