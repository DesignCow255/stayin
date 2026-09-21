<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Business-rule violation (validation passed, domain rejected the action).
 * Renders as 422 with a stable, client-safe error code.
 */
class BusinessException extends \RuntimeException
{
    /**
     * @param array<string, mixed> $context  — safe metadata for logs only
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'business_error',
        public readonly int $status = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $status, $previous);
    }
}
