#!/usr/bin/env php
<?php
$files = [
    'app/Core/Container.php',
    'app/Core/Router.php',
    'app/Core/Pipeline.php',
    'app/Core/HttpException.php',
    'app/Core/BusinessException.php',
    'app/Core/MiddlewareInterface.php',
    'app/Core/Migrator.php',
    'app/Core/Security.php',
    'app/Core/Request.php',
    'app/Core/Response.php',
    'app/Core/Controller.php',
    'app/Core/Validator.php',
    'app/Core/Session.php',
    'app/Core/Csrf.php',
    'app/Core/Env.php',
    'app/Core/Config.php',
    'app/Core/Database.php',
    'app/Core/Logger.php',
    'app/Core/RequestContext.php',
    'app/Core/ValidationException.php',
    'app/Support/helpers.php',
    'app/Middleware/VerifyCsrfToken.php',
    'app/Middleware/Authenticate.php',
    'app/Middleware/EnsureRole.php',
    'app/Middleware/EnsurePermission.php',
    'app/Middleware/EnsureAdminAccess.php',
    'app/Middleware/EnsureHost.php',
    'app/Middleware/EnsureEmailVerified.php',
    'app/Middleware/CheckMaintenanceMode.php',
    'app/Middleware/SecurityHeaders.php',
    'app/Middleware/SessionTarget.php',
    'app/Middleware/RedirectIfAuthenticated.php',
    'app/Middleware/ThrottleRequests.php',
    'app/Middleware/TrackLastActivity.php',
    'app/Middleware/SetLocale.php',
    'app/Middleware/ShareViewData.php',
    'app/Models/User.php',
    'app/Models/Property.php',
    'app/Models/HeroSlide.php',
    'app/Services/AuthService.php',
    'app/Services/Gate.php',
    'app/Services/RateLimiter.php',
    'app/Controllers/HomeController.php',
    'app/Controllers/AuthController.php',
    'app/Controllers/PropertyController.php',
    'app/Controllers/SearchController.php',
    'app/Controllers/ApiController.php',
    'app/Controllers/PageController.php',
    'app/Controllers/SeoController.php',
    'app/Controllers/HealthController.php',
    'bootstrap/app.php',
    'public/index.php',
    'routes/web.php',
    'routes/api.php',
];

$php = '/Applications/MAMP/bin/php/php8.3.14/bin/php';
$failed = [];
$passed = 0;

foreach ($files as $file) {
    $full = __DIR__ . '/' . $file;
    if (!is_file($full)) {
        echo "MISSING: $file\n";
        $failed[] = $file;
        continue;
    }
    $out = [];
    $rc = 0;
    exec("$php -l $full 2>&1", $out, $rc);
    $line = implode("\n", $out);
    if ($rc !== 0) {
        echo "FAIL: $file\n";
        echo "  $line\n";
        $failed[] = $file;
    } else {
        $passed++;
    }
}

echo "\n=== RESULTS ===\n";
echo "Passed: $passed\n";
echo "Failed: " . count($failed) . "\n";
if ($failed) {
    echo "Failed files:\n";
    foreach ($failed as $f) echo "  - $f\n";
    exit(1);
}
echo "All clear.\n";
