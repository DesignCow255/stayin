<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Central error rendering + exception handling.
 */
final class ErrorHandler
{
    /** @var array<int, string> */
    private const TITLES = [
        403 => 'Access denied',
        404 => 'Page not found',
        405 => 'Method not allowed',
        419 => 'Page expired',
        429 => 'Too many requests',
        500 => 'Something went wrong',
        503 => 'Temporarily unavailable',
    ];

    public static function register(): void
    {
        set_exception_handler(static function (Throwable $e): void {
            self::handleException($e);
        });

        set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                Logger::critical('fatal_error', ['message' => $error['message'], 'file' => $error['file'], 'line' => $error['line']]);
                if (!Config::bool('app.debug', false)) {
                    $response = Response::html(self::plainPage(500), 500);
                    $response->send();
                }
            }
        });
    }

    public static function handleException(Throwable $e): void
    {
        $isValidation = $e instanceof ValidationException;

        if ($isValidation) {
            Logger::info('validation.failed', ['errors' => array_keys($e->errors())]);
        } else {
            Logger::error('unhandled_exception', [
                'class' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        $status = $e instanceof HttpException ? $e->status : ($isValidation ? 422 : 500);
        $response = self::render($status, null, $e);
        $response->send();
    }

    /**
     * Render an error response. Falls back to a self-contained HTML page when
     * the view layer or database is unavailable.
     */
    public static function render(int $status, ?Request $request = null, ?Throwable $exception = null): Response
    {
        $debug = Config::bool('app.debug', false);
        $wantsJson = $request !== null && $request->expectsJson();

        if ($wantsJson) {
            $payload = [
                'ok' => false,
                'error' => [
                    'code' => self::code($status),
                    'message' => self::TITLES[$status] ?? 'Unexpected error',
                    'reference' => RequestContext::id(),
                ],
            ];
            if ($debug && $exception !== null) {
                $payload['error']['debug'] = [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ];
            }
            return Response::json($payload, $status);
        }

        try {
            if (View::exists('errors/error')) {
                return View::render('errors/error', [
                    'status' => $status,
                    'title' => self::TITLES[$status] ?? 'Unexpected error',
                    'reference' => RequestContext::id(),
                    'debug' => $debug,
                    'exception' => $exception,
                    'metaTitle' => (self::TITLES[$status] ?? 'Error') . ' — StayIn',
                    'noindex' => true,
                ], 'layouts/app')->withStatus($status);
            }
        } catch (Throwable) {
            // fall through to plain page
        }

        return Response::html(self::plainPage($status, $exception, $debug), $status);
    }

    public static function plainPage(int $status, ?Throwable $exception = null, bool $debug = false): string
    {
        $title = htmlspecialchars(self::TITLES[$status] ?? 'Unexpected error', ENT_QUOTES);
        $reference = htmlspecialchars(RequestContext::id(), ENT_QUOTES);
        $detail = '';

        if ($debug && $exception !== null) {
            $detail = '<pre style="white-space:pre-wrap;text-align:left;background:#111;color:#e5e5e5;padding:16px;border-radius:8px;font-size:13px">'
                . htmlspecialchars($exception::class . ': ' . $exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine(), ENT_QUOTES)
                . '</pre>';
        }

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $title . ' — StayIn</title><meta name="robots" content="noindex">'
            . '<style>body{margin:0;background:#0b0f14;color:#f5f5f4;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;display:grid;'
            . 'place-items:center;min-height:100vh;padding:24px}main{max-width:640px}h1{font-size:clamp(2rem,6vw,3rem);margin:0 0 8px}'
            . 'p{color:#a1a1aa;line-height:1.6}a{color:#d4a373}</style></head><body><main>'
            . '<h1>' . $title . '</h1><p>Reference: <code>' . $reference . '</code></p>'
            . $detail
            . '<p><a href="/">Return to StayIn</a></p></main></body></html>';
    }

    public static function code(int $status): string
    {
        return match ($status) {
            403 => 'forbidden',
            404 => 'not_found',
            405 => 'method_not_allowed',
            419 => 'csrf_token_expired',
            422 => 'validation_failed',
            429 => 'rate_limited',
            503 => 'unavailable',
            default => 'server_error',
        };
    }
}