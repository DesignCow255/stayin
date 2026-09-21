<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Helpers shared by the guard middleware family (session "intended URL").
 */
final class SessionTarget
{
    public static function storeIntended(string $path): void
    {
        Session::start();
        Session::put('intended_url', $path);
    }

    public static function pullIntended(string $fallback = '/'): string
    {
        $url = Session::pull('intended_url', $fallback);
        return is_string($url) && $url !== '' ? $url : $fallback;
    }
}

final class SessionFlash
{
    /**
     * Pull a pending flash status message set by controllers/services.
     *
     * @return array{type: string, message: string}|null
     */
    public static function pull(): ?array
    {
        $raw = Session::pull('status');
        if (is_array($raw) && isset($raw['message'])) {
            return ['type' => (string) ($raw['type'] ?? 'info'), 'message' => (string) $raw['message']];
        }
        if (is_string($raw) && $raw !== '') {
            return ['type' => 'info', 'message' => $raw];
        }
        return null;
    }
}

final class View503
{
    public static function render(): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Maintenance — StayIn</title><meta name="robots" content="noindex">'
            . '<style>body{margin:0;background:#0b0f14;color:#f5f5f4;font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;text-align:center;padding:24px}</style>'
            . '</head><body><main><h1>We will be right back</h1><p>StayIn is undergoing scheduled maintenance.</p></main></body></html>';
    }
}
