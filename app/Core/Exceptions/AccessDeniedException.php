<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class AccessDeniedException extends HttpException
{
    public function __construct(string $message = 'You are not allowed to perform this action.', ?\Throwable $previous = null)
    {
        parent::__construct(403, $message, [], $previous);
    }
}