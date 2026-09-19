<?php
$files = [
    'app/Core/App.php',
    'app/Core/Autoloader.php',
    'app/Core/Container.php',
    'app/Core/Config.php',
    'app/Core/Env.php',
    'app/Core/Database.php',
    'app/Core/Logger.php',
    'app/Core/Request.php',
    'app/Core/Response.php',
    'app/Core/Router.php',
    'app/Core/Session.php',
    'app/Core/Validator.php',
    'app/Core/Csrf.php',
    'app/Core/Security.php',
    'app/Core/View.php',
    'app/Core/ErrorHandler.php',
    'app/Core/Encryption.php',
    'app/Core/Cryptography.php',
    'app/Core/Timezone.php',
    'app/Core/Locale.php',
    'app/Core/JsonEncoder.php',
    'app/Core/Pipeline.php',
    'app/Core/Middleware.php',
    'app/Core/MiddlewareInterface.php',
    'app/Core/Http/MiddlewareInterface.php',
    'app/Core/Route.php',
    'app/Core/RouteDefinition.php',
    'app/Core/RouteRegistrar.php',
    'app/Core/Exceptions/',
    'app/Middleware/',
    'bootstrap/app.php',
    'public/index.php',
    'config/*.php',
];
$php = '/Applications/MAMP/bin/php/php8.3.14/bin/php';
$failed = 0;
foreach ($files as $pattern) {
    if (substr($pattern, -1) === '/') {
        foreach (glob($pattern . '*.php') as $file) {
            echo " {$file}\n";
            exec("{$php} -l {$file} 2>&1", $out, $rc);
            if ($rc !== 0) { echo "   FAILED\n"; $failed++; }
        }
    } else {
        echo " {$pattern}\n";
        if (is_file($pattern)) {
            exec("{$php} -l {$pattern} 2>&1", $out, $rc);
            if ($rc !== 0) { echo "   FAILED\n"; $failed++; }
        } else {
            echo "   (not a file)\n";
        }
    }
}
echo "\nTotal failures: {$failed}\n";
