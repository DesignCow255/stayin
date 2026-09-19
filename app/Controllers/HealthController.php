<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Migrator;

/**
 * Safe health endpoint. Reports only non-sensitive operational state.
 */
final class HealthController extends Controller
{
    public function health(Request $request): Response
    {
        $checks = [
            'app' => 'ok',
            'database' => 'unknown',
            'migrations' => 'unknown',
            'cache' => is_writable(config('app.base_path') . '/storage/cache') ? 'ok' : 'not_writable',
        ];

        try {
            Database::scalar('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable) {
            $checks['database'] = 'unavailable';
        }

        if ($checks['database'] === 'ok') {
            try {
                $migrator = new Migrator();
                $pending = $migrator->unappliedCount();
                $checks['migrations'] = $pending > 0 ? ('pending:' . $pending) : 'ok';
            } catch (\Throwable) {
                $checks['migrations'] = 'unavailable';
            }
        }

        $healthy = !in_array('unavailable', $checks, true);

        return $this->json([
            'ok' => $healthy,
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'time' => gmdate('c'),
        ], $healthy ? 200 : 503);
    }
}
