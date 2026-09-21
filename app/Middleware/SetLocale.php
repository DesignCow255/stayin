<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

final class SetLocale implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $supported = array_keys((array) Config::get('locale.locales', ['en' => 'English']));
        $locale = (string) $request->query('lang', '');

        if ($locale !== '' && in_array($locale, $supported, true)) {
            setcookie('stayin_locale', $locale, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
        } else {
            $locale = (string) ($_COOKIE['stayin_locale'] ?? '');
        }

        if (!in_array($locale, $supported, true)) {
            $locale = Config::string('locale.default_locale', 'en');
        }

        $GLOBALS['stayin_locale'] = $locale;

        return $next($request);
    }
}
