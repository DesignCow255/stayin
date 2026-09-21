<?php

/**
 * Public web routes.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PropertyController;
use App\Controllers\SearchController;

// --- Health & robots (no session cost) -------------------------------------
$router->get('/health', 'HealthController@health', 'health');
$router->get('/robots.txt', 'SeoController@robots', 'robots');
$router->get('/sitemap.xml', 'SeoController@sitemapIndex', 'sitemap.index');
$router->get('/sitemap-properties.xml', 'SeoController@sitemapProperties', 'sitemap.properties');
$router->get('/sitemap-destinations.xml', 'SeoController@sitemapDestinations', 'sitemap.destinations');
$router->get('/sitemap-pages.xml', 'SeoController@sitemapPages', 'sitemap.pages');

// --- Discovery --------------------------------------------------------------
$router->get('/', 'HomeController@index', 'home')->middleware(['role_home']);
$router->get('/search', 'SearchController@index', 'search');
$router->get('/stays', 'PropertyController@index', 'stays.index');
$router->get('/stays/{region}', 'PropertyController@byRegion', 'stays.region')->where(['region' => '[a-z0-9-]+']);
$router->get('/property/{slug}', 'PropertyController@show', 'property.show')->where(['slug' => '[a-z0-9-]+']);

// --- Static / legal ----------------------------------------------------------
$router->get('/about', 'PageController@about', 'about');
$router->get('/contact', 'PageController@contact', 'contact');
$router->get('/privacy', 'PageController@privacy', 'privacy');
$router->get('/terms', 'PageController@terms', 'terms');
$router->get('/help', 'PageController@help', 'help');

// --- Authentication -----------------------------------------------------------
$router->get('/login', 'AuthController@showLogin', 'login')->middleware(['guest']);
$router->post('/login', 'AuthController@login', 'login.attempt')->middleware(['throttle:login']);
$router->get('/register', 'AuthController@showRegister', 'register')->middleware(['guest']);
$router->post('/register', 'AuthController@register', 'register.attempt')->middleware(['throttle:register']);
$router->post('/logout', 'AuthController@logout', 'logout')->middleware(['auth']);

$router->get('/forgot-password', 'AuthController@showForgot', 'password.request');
$router->post('/forgot-password', 'AuthController@sendReset', 'password.email')->middleware(['throttle:password_reset']);
$router->get('/reset-password/{token}', 'AuthController@showReset', 'password.reset')->where(['token' => '[a-f0-9]{64}']);
$router->post('/reset-password', 'AuthController@reset', 'password.update')->middleware(['throttle:password_reset']);

$router->get('/verify-email', 'AuthController@verifyNotice', 'verification.notice');
$router->post('/verify-email/resend', 'AuthController@resendVerification', 'verification.resend')->middleware(['auth','throttle:password_reset']);
$router->get('/verify-email/{token}', 'AuthController@verify', 'verification.verify')->where(['token' => '[a-f0-9]{64}']);

// --- Guest area -----------------------------------------------------------
$router->group(['prefix' => '/guest', 'middleware' => ['auth','verified','guest_only']], static function (App\Core\Router $router): void {
    $router->get('', 'GuestController@index', 'guest.dashboard');
    $router->get('/bookings', 'GuestController@index', 'guest.bookings');
    $router->get('/favourites', 'GuestController@index', 'guest.favourites');
    $router->get('/profile', 'GuestController@index', 'guest.profile');
    $router->post('/profile', 'GuestController@profile', 'guest.profile.update');
});

// --- Host portal -----------------------------------------------------------
$router->group(['prefix' => '/host', 'middleware' => ['auth','verified','host']], static function (App\Core\Router $router): void {
    $router->get('', 'HostController@index', 'host.dashboard');
    $router->get('/properties', 'HostController@index', 'host.properties');
    $router->get('/properties/create', 'HostController@create', 'host.properties.create');
    $router->post('/properties', 'HostController@store', 'host.properties.store');
    $router->get('/properties/{id}/edit', 'HostController@edit', 'host.properties.edit')->where(['id' => '[0-9]+']);
    $router->post('/properties/{id}', 'HostController@update', 'host.properties.update')->where(['id' => '[0-9]+']);
    $router->get('/bookings', 'HostController@index', 'host.bookings');
});

// --- Admin control centre ---------------------------------------------------
$router->group(['prefix' => '/admin', 'middleware' => ['auth','verified','admin']], static function (App\Core\Router $router): void {
    $router->get('', 'AdminController@index', 'admin.dashboard');
});

// --- Booking flow (session-aware, guest checkout gated per config) ----------
$router->get('/checkout/{reference}', 'BookingController@showCheckout', 'checkout.show')->middleware(['auth'])->where(['reference' => '[A-Z0-9-]{4,30}']);
$router->post('/checkout/{reference}/pay', 'BookingController@pay', 'checkout.pay')->middleware(['auth','throttle:payment_init']);

$router->post('/preferences', 'PreferenceController@save');
$router->post('/newsletter', 'PreferenceController@newsletter')->middleware(['throttle:register']);
$router->get('/newsletter/{action}/{token}', 'PreferenceController@newsletterToken');
$router->post('/newsletter/{action}/{token}', 'PreferenceController@newsletterToken');
$router->post('/bookings/hold', 'BookingController@hold')->middleware(['auth','throttle:hold_create']);
$router->post('/bookings/{reference}/cancel', 'BookingController@cancel')->middleware(['auth'])->where(['reference' => '[A-Z0-9-]{4,30}']);
$router->get('/bookings/{reference}/receipt', 'BookingController@receipt')->middleware(['auth'])->where(['reference' => '[A-Z0-9-]{4,30}']);
$router->post('/guest/favourites', 'GuestController@favourite')->middleware(['auth']);
$router->post('/guest/notifications/read', 'GuestController@read')->middleware(['auth']);
$router->post('/guest/reviews', 'GuestController@review')->middleware(['auth']);
$router->post('/guest/searches', 'GuestController@saveSearch')->middleware(['auth']);
$router->get('/guest/export', 'GuestController@export')->middleware(['auth']);
$router->post('/guest/privacy', 'GuestController@privacy')->middleware(['auth']);
$router->get('/messages/{id}', 'MessageController@index')->middleware(['auth']);
$router->post('/messages/{id}', 'MessageController@send')->middleware(['auth','throttle:api']);
$router->post('/host/properties/{id}/actions', 'HostController@action')->middleware(['auth','verified','host']);
$router->post('/host/kyc', 'HostController@kyc')->middleware(['auth','verified','host']);
$router->post('/host/bookings/complete', 'HostController@complete')->middleware(['auth','verified','host']);
$router->post('/admin/actions', 'AdminController@action')->middleware(['auth','verified','admin']);
$router->get('/admin/kyc/{id}/document', 'AdminController@document')->middleware(['auth','verified','admin']);
$router->get('/admin/content', 'AdminController@content')->middleware(['auth','verified','admin']);
$router->post('/admin/content', 'AdminController@saveContent')->middleware(['auth','verified','admin']);
$router->get('/exports/bookings', 'ExportController@bookings')->middleware(['auth']);
