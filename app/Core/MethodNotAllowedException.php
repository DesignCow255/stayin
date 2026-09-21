<?php

declare(strict_types=1);

namespace App\Core;

final class MethodNotAllowedException extends \RuntimeException
{
    /**
     * @param array<int, string> $allowed
     */
    public function __construct(public readonly array $allowed = [])
    {
        parent::__construct('Method not allowed');
    }
}