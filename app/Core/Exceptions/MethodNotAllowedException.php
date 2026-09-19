<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class MethodNotAllowedException extends HttpException
{
    /**
     * @param array<int, string> $allowed
     */
    public function __construct(array $allowed = [])
    {
        parent::__construct(
            405,
            'That request method is not supported for this address.',
            ['Allow' => implode(', ', $allowed)]
        );
    }
}
