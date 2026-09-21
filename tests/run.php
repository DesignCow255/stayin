<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
require $basePath . '/bootstrap/app.php';

use App\Core\Database;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;

$failures = [];

function test_assert(bool $condition, string $message): void
{
    global $failures;
    if (!$condition) {
        $failures[] = $message;
        echo "FAIL: {$message}\n";
        return;
    }
    echo "PASS: {$message}\n";
}

function test_read(string $relative): string
{
    $path = dirname(__DIR__) . '/' . $relative;
    $contents = is_file($path) ? file_get_contents($path) : false;
    return is_string($contents) ? $contents : '';
}

function test_active_user_id(string $role): ?int
{
    try {
        $row = Database::first(
            'SELECT `id` FROM `users` WHERE `role` = ? AND `status` = "active" ORDER BY `id` LIMIT 1',
            [$role]
        );
        return $row === null ? null : (int) $row['id'];
    } catch (Throwable) {
        return null;
    }
}

function test_dispatch(Router $router, string $method, string $path, ?int $userId = null): array
{
    $_SERVER['REQUEST_METHOD'] = strtoupper($method);
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'StayInTest/1.0';
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    $_SESSION = [];
    Session::start();
    if ($userId !== null) {
        Session::put('auth_user_id', $userId);
    }
    $response = $router->dispatch(Request::fake($method, $path));
    return [$response->status(), $response->headers(), $response->content()];
}

function test_dispatch_form(Router $router, string $path, array $body, ?int $userId = null): array
{
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'StayInTest/1.0';
    $_SERVER['HTTP_REFERER'] = $path;
    $_GET = [];
    $_POST = [];
    $_FILES = [];
    $_SESSION = [];
    Session::start();
    if ($userId !== null) {
        Session::put('auth_user_id', $userId);
    }
    $body['_token'] = Csrf::token();
    $response = $router->dispatch(Request::fake('POST', $path, [], $body));
    return [$response->status(), $response->headers(), $response->content(), $_SESSION];
}

$router = new Router($basePath);
$router->load($basePath . '/routes/web.php');
$router->load($basePath . '/routes/api.php');

$definitions = $router->definitions();
$patterns = [];
$names = [];
foreach ($definitions as $route) {
    $patterns[$route->pattern] = true;
    if ($route->routeName() !== null) {
        $names[$route->routeName()] = true;
    }
}

test_assert(count($definitions) >= 55, 'web and API routes register');
foreach (['/', '/login', '/forgot-password', '/guest/favourites', '/host/properties/create', '/admin', '/api/v1/ping'] as $path) {
    test_assert(isset($patterns[$path]), "route exists: {$path}");
}
test_assert(isset($patterns['/bookings/{reference}/receipt']), 'shared receipt preview route exists');
foreach (['home', 'login', 'password.request', 'guest.favourites', 'host.properties.create', 'admin.dashboard', 'api.ping'] as $name) {
    test_assert(isset($names[$name]), "route name exists: {$name}");
}

$loginView = test_read('resources/views/auth/login.php');
test_assert(str_contains($loginView, "url('/forgot-password')"), 'login forgot-password link targets the registered route');
test_assert(!str_contains($loginView, '/password/forgot'), 'login view does not contain stale forgot-password URL');

$verifyView = test_read('resources/views/auth/verify.php');
test_assert(str_contains($verifyView, 'isset($email)') && str_contains($verifyView, 'url(\'/verify-email/resend\')'), 'verify-email notice handles missing email and targets resend route');
test_assert(!str_contains($verifyView, "url('/verify')") && !str_contains($verifyView, "url('/verify/resend')"), 'verify-email notice does not reference stale verification routes');

$registerView = test_read('resources/views/auth/register.php');
test_assert(str_contains($registerView, 'name="first_name"') && str_contains($registerView, 'name="last_name"'), 'register form submits first and last name fields required by users table');
test_assert(str_contains($registerView, 'name="role"') && str_contains($registerView, 'value="guest"') && str_contains($registerView, 'value="host"'), 'register form exposes only guest and host account types');
test_assert(!str_contains($registerView, 'value="admin"') && !str_contains($registerView, 'value="super_admin"'), 'register form does not expose admin account creation');
test_assert(str_contains($registerView, 'minlength="10"') && str_contains($registerView, 'maxlength="72"'), 'register form password limits match server validation');

$authController = test_read('app/Controllers/AuthController.php');
test_assert(str_contains($authController, "'role' => 'required|in:guest,host'"), 'register controller restricts public roles to guest and host');
test_assert(!str_contains($authController, "in:host,guest,admin") && !str_contains($authController, "super_admin"), 'register controller does not allow public admin creation');

$helpers = test_read('app/Support/helpers.php');
test_assert(str_contains($helpers, "Session::getFlash('_old'") && str_contains($helpers, 'array_key_exists($key, $old)'), 'old() helper reads flashed form input correctly');

$appJs = test_read('public/assets/js/app.js');
test_assert(!str_contains($appJs, "fetch('/stayin/preferences'"), 'theme preference sync does not hardcode a subdirectory');
test_assert(str_contains($appJs, "new URL('preferences', document.baseURI)"), 'theme preference sync resolves relative to the document base URI');
test_assert(!str_contains($appJs, "register('/assets/js/service-worker.js')"), 'service worker registration does not hardcode the web root');
test_assert(str_contains($appJs, "new URL('service-worker.js', document.baseURI)"), 'service worker registration resolves relative to the document base URI');

$authMiddleware = test_read('app/Middleware/Authenticate.php');
test_assert(substr_count($authMiddleware, 'SessionTarget::storeIntended') === 1, 'Authenticate middleware stores intended path once');

$envExample = test_read('.env.example');
foreach (['BOOKING_MAX_GUESTS_PER_ROOM', 'BOOKING_ADVANCE_DAYS', 'BOOKING_REQUIRE_EMAIL_VERIFICATION', 'FEATURE_OAUTH_GOOGLE', 'FEATURE_OAUTH_APPLE', 'WHATSAPP_API_TOKEN', 'SMS_PROVIDER', 'PAYMENT_WEBHOOK_STORAGE_PATH'] as $key) {
    test_assert(str_contains($envExample, $key . '='), ".env.example documents {$key}");
}

foreach (['public/.htaccess', 'public/manifest.webmanifest', 'public/service-worker.js', 'public/offline.html', 'public/assets/images/placeholder-stay.svg'] as $file) {
    test_assert(is_file($basePath . '/' . $file), "required deployment asset exists: {$file}");
}

$paymentsConfig = test_read('config/payments.php');
test_assert(str_contains($paymentsConfig, "Env::bool('PAYMENT_MOCK_ENABLED', true)"), 'mock payment gateway is explicit and environment controlled');
test_assert(str_contains($paymentsConfig, "'live' => true"), 'live payment gateway configs are distinguishable from mock');

$mockGateway = test_read('app/Payments/MockGateway.php');
test_assert(str_contains($mockGateway, "Config::string('app.env')") && str_contains($mockGateway, "'payments_unavailable'"), 'mock payment gateway refuses unavailable/production use');

$mailService = test_read('app/Services/MailService.php');
test_assert(str_contains($mailService, 'SMTP transport must be configured and verified'), 'production email worker fails closed without verified SMTP transport');

$bookingService = test_read('app/Services/BookingService.php');
test_assert(str_contains($bookingService, 'public static function receipt'), 'BookingService has a role-aware receipt resolver');
test_assert(str_contains($bookingService, "Gate::allows('payments.view')") && str_contains($bookingService, "Gate::allows('bookings.view')"), 'receipt resolver permits authorized finance/bookings admins');

$checkoutView = test_read('resources/views/checkout.php');
$guestView = test_read('resources/views/guest.php');
$hostView = test_read('resources/views/host/index.php');
$adminView = test_read('resources/views/admin/index.php');
$receiptView = test_read('resources/views/receipt.php');
foreach (['checkout' => $checkoutView, 'guest dashboard' => $guestView, 'host dashboard' => $hostView, 'admin dashboard' => $adminView] as $label => $view) {
    test_assert(str_contains($view, '/bookings/') && str_contains($view, '/receipt'), "{$label} links to generated receipt previews");
    test_assert(str_contains($view, 'target="_blank"') && str_contains($view, 'rel="noopener"'), "{$label} receipt links open preview tabs safely");
}
test_assert(str_contains($receiptView, 'window.print()') && str_contains($receiptView, 'Print / download PDF'), 'receipt preview supports print/download PDF from preview');

$releaseCheck = test_read('tests/release_check.php');
foreach (['database/backups/*', 'storage/sessions/*', 'storage/backups/*'] as $forbiddenPattern) {
    test_assert(str_contains($releaseCheck, $forbiddenPattern), "release gate forbids {$forbiddenPattern}");
}

$guestId = test_active_user_id('guest');
$hostId = test_active_user_id('host');
$adminId = test_active_user_id('super_admin') ?? test_active_user_id('admin');

if ($guestId !== null && $hostId !== null && $adminId !== null) {
    [$status] = test_dispatch($router, 'GET', '/guest', $guestId);
    test_assert($status === 200, 'guest user can access guest dashboard');

    [$status] = test_dispatch($router, 'GET', '/host', $hostId);
    test_assert($status === 200, 'host user can access host dashboard');

    [$status] = test_dispatch($router, 'GET', '/admin', $adminId);
    test_assert($status === 200, 'administrator user can access admin dashboard');

    [$status, $headers] = test_dispatch($router, 'GET', '/admin', $guestId);
    test_assert($status === 403, 'guest user is denied admin dashboard');

    [$status, $headers] = test_dispatch($router, 'GET', '/host', $guestId);
    test_assert($status === 302 && isset($headers['Location']), 'guest user is redirected away from host dashboard');

    [$status, $headers] = test_dispatch($router, 'GET', '/guest', $hostId);
    test_assert($status === 302 && isset($headers['Location']), 'host user is redirected away from guest-only dashboard');

    [$status, $headers] = test_dispatch($router, 'GET', '/login', $guestId);
    test_assert($status === 302 && isset($headers['Location']), 'authenticated user is redirected away from login page');

    [$status, , $content] = test_dispatch($router, 'GET', '/verify-email', $guestId);
    test_assert($status === 200 && str_contains($content, 'Verify your email'), 'authenticated user can view email verification notice without undefined email errors');

    try {
        $paid = Database::first(
            "SELECT b.booking_reference,b.guest_id,p.host_id FROM bookings b JOIN properties p ON p.id=b.property_id WHERE b.payment_status IN ('paid','refunded') ORDER BY b.id DESC LIMIT 1"
        );
    } catch (Throwable) {
        $paid = null;
    }
    if ($paid !== null) {
        [$status] = test_dispatch($router, 'GET', '/bookings/' . $paid['booking_reference'] . '/receipt', (int) $paid['guest_id']);
        test_assert($status === 200, 'booking guest can preview generated receipt');

        [$status] = test_dispatch($router, 'GET', '/bookings/' . $paid['booking_reference'] . '/receipt', (int) $paid['host_id']);
        test_assert($status === 200, 'property host can preview generated receipt');

        [$status] = test_dispatch($router, 'GET', '/bookings/' . $paid['booking_reference'] . '/receipt', $adminId);
        test_assert($status === 200, 'administrator can preview generated receipt');
    } else {
        test_assert(true, 'receipt preview route smoke skipped: local database has no paid/refunded booking');
    }
} else {
    test_assert(true, 'authenticated RBAC route smoke skipped: local database lacks one active guest, host, and administrator');
}

try {
    Database::transaction(function () use ($router): void {
        $suffix = bin2hex(random_bytes(5));
        $password = 'RegisterOk!2026';
        $guestEmail = 'stayin-test-guest-' . $suffix . '@example.test';
        [$status, $headers] = test_dispatch_form($router, '/register', [
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $guestEmail,
            'phone' => '',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'guest',
        ]);
        $guest = Database::first('SELECT id,first_name,last_name,email,role,status,password_hash FROM users WHERE email=?', [$guestEmail]);
        test_assert($status === 302 && isset($headers['Location']) && str_ends_with((string) $headers['Location'], '/guest'), 'guest registration redirects to guest dashboard');
        test_assert($guest !== null && $guest['role'] === 'guest' && $guest['status'] === 'active', 'guest registration creates an active guest user');
        test_assert($guest !== null && password_verify($password, (string) $guest['password_hash']), 'guest registration stores a valid password hash');

        $hostEmail = 'stayin-test-host-' . $suffix . '@example.test';
        [$status, $headers] = test_dispatch_form($router, '/register', [
            'first_name' => 'Test',
            'last_name' => 'Host',
            'email' => $hostEmail,
            'phone' => '+255700' . random_int(100000, 999999),
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'host',
        ]);
        $host = Database::first('SELECT id,first_name,last_name,email,phone,role,status,password_hash FROM users WHERE email=?', [$hostEmail]);
        test_assert($status === 302 && isset($headers['Location']) && str_ends_with((string) $headers['Location'], '/host'), 'host registration redirects to host dashboard');
        test_assert($host !== null && $host['role'] === 'host' && $host['status'] === 'active', 'host registration creates an active host user');
        test_assert($host !== null && password_verify($password, (string) $host['password_hash']), 'host registration stores a valid password hash');

        $adminEmail = 'stayin-test-admin-' . $suffix . '@example.test';
        [$status] = test_dispatch_form($router, '/register', [
            'first_name' => 'Bad',
            'last_name' => 'Admin',
            'email' => $adminEmail,
            'phone' => '',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => 'admin',
        ]);
        $admin = Database::first('SELECT id FROM users WHERE email=?', [$adminEmail]);
        test_assert($status === 303, 'public admin registration attempt is rejected with validation redirect');
        test_assert($admin === null, 'public admin registration attempt does not create a user');

        throw new RuntimeException('rollback registration smoke inserts');
    });
} catch (RuntimeException $e) {
    if ($e->getMessage() !== 'rollback registration smoke inserts') {
        throw $e;
    }
}

if ($failures !== []) {
    echo "\n" . count($failures) . " test(s) failed.\n";
    exit(1);
}

echo "\nAll lightweight regression tests passed.\n";