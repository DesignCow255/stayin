<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response value object + helpers.
 */
final class Response
{
    /** @var array<string, string> */
    private array $headers = [];
    private int $status = 200;
    private string $content = '';

    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new self(
            $payload === false ? '{}' : $payload,
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8', ...$headers]
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        if (str_starts_with($location, '/') && !str_starts_with($location, '//')) $location = url($location);
        return new self('', $status, ['Location' => $location, 'Cache-Control' => 'no-store']);
    }

    public static function text(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public static function xml(string $content, int $status = 200): self
    {
        return new self($content, $status, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    /**
     * @param array<string, string> $headers
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->headers = [...$clone->headers, ...$headers];
        return $clone;
    }

    public function withStatus(int $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value, true);
            }
        }
        echo $this->content;
    }
}