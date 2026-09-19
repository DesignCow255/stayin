<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class BusinessException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'business_error',
        public readonly int $statusCode = 422
    ) {
        parent::__construct($message, $statusCode);
    }
}