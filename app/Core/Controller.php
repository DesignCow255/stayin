<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller with rendering, redirect and JSON conveniences.
 */
abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($view, $data), $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json(['ok' => $status < 400, ...$data], $status);
    }

    protected function redirect(string $to, int $status = 302): Response
    {
        return Response::redirect($to, $status);
    }

    protected function redirectToRoute(string $name, array $params = []): Response
    {
        return Response::redirect(App::router()->route($name, $params));
    }

    protected function back(string $fallback = '/'): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = parse_url($referer, PHP_URL_HOST);
        $appHost = parse_url(Config::string('app.url'), PHP_URL_HOST);

        if ($referer !== '' && ($host === null || $host === $appHost)) {
            return Response::redirect($referer);
        }

        return Response::redirect($fallback);
    }

    /**
     * @param array<string, mixed> $extra
     */
    protected function errorPage(int $status, string $message, array $extra = []): Response
    {
        $template = match ($status) {
            403 => 'errors/403',
            419, 429 => 'errors/429',
            503 => 'errors/503',
            default => 'errors/500',
        };

        if ($extra === [] && $status === 404) {
            $template = 'errors/404';
        }

        return Response::html(View::render($template, [
            'title' => $message,
            'message' => $message,
            'status' => $status,
            ...$extra,
        ]), $status);
    }
}
