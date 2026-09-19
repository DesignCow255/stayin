<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class CsrfException extends HttpException
{
    public function __construct(string $message = 'Your security token expired. Please try again.', ?\Throwable $previous = null)
    {
        parent::__construct(419, $message, [], $previous);
    }
}