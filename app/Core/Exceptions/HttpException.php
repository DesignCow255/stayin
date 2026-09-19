<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

class HttpException extends \RuntimeException
{
    /** @var array<string, string> */
    private array $httpHeaders;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, string $message = '', array $headers = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->httpHeaders = $headers;
    }

    public function statusCode(): int
    {
        return $this->getCode() ?: 500;
    }

    /**
     * @return array<string, string>
     */
    public function httpHeaders(): array
    {
        return $this->httpHeaders;
    }
}