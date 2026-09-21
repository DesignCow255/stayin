<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$failures = [];

$forbiddenGlobs = [
    'database/backups/*' => 'database backups may contain user data and password hashes',
    'storage/sessions/*' => 'runtime sessions may contain authenticated session tokens',
    'storage/backups/*' => 'local backup snapshots must not be part of a release artifact',
];

foreach ($forbiddenGlobs as $pattern => $reason) {
    foreach (glob($basePath . '/' . $pattern, GLOB_NOSORT) ?: [] as $path) {
        $name = basename($path);
        if ($name === '.gitkeep' || $name === '.DS_Store') {
            continue;
        }
        $failures[] = sprintf('%s present (%s)', substr($path, strlen($basePath) + 1), $reason);
    }
}

$trackedForbidden = trim((string) shell_exec('git -C ' . escapeshellarg($basePath) . ' ls-files database/backups storage/sessions storage/backups 2>/dev/null'));
if ($trackedForbidden !== '') {
    foreach (explode("\n", $trackedForbidden) as $tracked) {
        if (trim($tracked) !== '') {
            $failures[] = 'forbidden tracked file: ' . trim($tracked);
        }
    }
}

$requiredFiles = [
    'public/index.php',
    'public/.htaccess',
    'public/manifest.webmanifest',
    'public/service-worker.js',
    'public/offline.html',
    'public/assets/js/app.js',
    'public/assets/images/placeholder-stay.svg',
    'AUDIT_REPORT.md',
    'DEPLOYMENT_CHECKLIST.md',
    'REMAINING_ISSUES.md',
];

foreach ($requiredFiles as $file) {
    if (!is_file($basePath . '/' . $file)) {
        $failures[] = 'required release file missing: ' . $file;
    }
}

if ($failures !== []) {
    echo "Release check failed:\n";
    foreach ($failures as $failure) {
        echo ' - ' . $failure . "\n";
    }
    echo "\nCreate a clean release artifact that excludes runtime backups/sessions/secrets, or move them to approved private storage before packaging.\n";
    exit(1);
}

echo "Release check passed: no forbidden backup/session/secret artifacts detected in the release tree.\n";