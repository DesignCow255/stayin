<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * HTTP exception carrying a status code, used by middleware and policies.
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly ?string $redirectTo = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message !== '' ? $message : ('HTTP ' . $status), $status, $previous);
    }

    public static function forbidden(string $message = 'You do not have permission to perform this action.'): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = 'The requested resource could not be found.'): self
    {
        return new self(404, $message);
    }

    public static function expired(string $message = 'Your session expired. Please try again.'): self
    {
        return new self(419, $message);
    }

    public static function tooManyRequests(string $message = 'Too many requests. Please slow down.'): self
    {
        return new self(429, $message);
    }

    public static function unavailable(string $message = 'The service is temporarily unavailable.'): self
    {
        return new self(503, $message);
    }

    public static function redirect(string $url, string $message = ''): self
    {
        return new self(302, $message, $url);
    }
}