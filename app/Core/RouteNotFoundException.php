<?php

declare(strict_types=1);

namespace App\Core;

final class RouteNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Route not found')
    {
        parent::__construct($message);
    }
}