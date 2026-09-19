<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request object built from PHP superglobals.
 */
final class Request
{
    /** @var array<string, mixed> */
    private array $query;
    /** @var array<string, mixed> */
    private array $body;
    /** @var array<string, mixed> */
    private array $files;
    /** @var array<string, array<int, string>> */
    private array $headers;
    /** @var array<string, string> */
    private array $routeParams = [];
    private string $method;
    private string $path;
    private string $ip;
    private ?string $userAgent;

    private function __construct()
    {
        $this->query = $_GET;
        $this->files = $_FILES;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = self::resolvePath();
        $this->ip = self::resolveIp();
        $this->userAgent = isset($_SERVER['HTTP_USER_AGENT'])
            ? mb_substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 500)
            : null;
        $this->headers = self::collectHeaders();
        $this->body = $this->collectBody();
    }

    public static function capture(): self
    {
        return new self();
    }

    /**
     * Allow CLI jobs and tests to fabricate a request.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public static function fake(string $method, string $path, array $query = [], array $body = []): self
    {
        $request = new self();
        $request->method = strtoupper($method);
        $request->path = '/' . trim($path, '/');
        $request->query = $query;
        $request->body = $body;

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectBody(): array
    {
        if ($this->method === 'GET' || $this->method === 'HEAD') {
            return [];
        }

        $contentType = strtolower($this->header('Content-Type') ?? '');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if ($raw === false || $raw === '') {
                return [];
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [];
        }
        $parsed = [];
        parse_str($raw, $parsed);
        return $parsed;
    }

    private static function resolvePath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    private static function resolveIp(): string
    {
        if (Config::bool('app.trust_proxy_headers', false)) {
            foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
                if (!empty($_SERVER[$header])) {
                    $candidate = trim(explode(',', (string) $_SERVER[$header])[0]);
                    if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                        return $candidate;
                    }
                }
            }
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        return filter_var($ip, FILTER_VALIDATE_IP) ? (string) $ip : '0.0.0.0';
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name][] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $name = strtolower(str_replace('_', '-', $key));
                $headers[$name][] = $value;
            }
        }
        return $headers;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    /**
     * Support HTML form method spoofing via `_method`.
     */
    public function spoofedMethod(): string
    {
        if ($this->method === 'POST') {
            $spoof = strtoupper((string) ($this->body['_method'] ?? ''));
            if (in_array($spoof, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $spoof;
            }
        }
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function url(): string
    {
        return rtrim(Config::string('app.url'), '/') . ($this->path === '/' ? '' : $this->path);
    }

    public function fullUrl(): string
    {
        $query = http_build_query($this->query);
        return $this->url() . ($query !== '' ? '?' . $query : '');
    }

    public function ip(): string
    {
        return $this->ip;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_array($value) ? $default : (string) $value;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key, $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->input($key);
        if ($value === null) {
            return $default;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [...$this->query, ...$this->body];
    }

    /**
     * @param array<int, string> $keys
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $all = $this->all();
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /**
     * Only parameters that were actually submitted (keeps URLs clean).
     *
     * @return array<string, mixed>
     */
    public function filled(): array
    {
        return array_filter($this->all(), static fn ($v): bool => $v !== null && $v !== '' && $v !== []);
    }

    /**
     * @return array<string, mixed>
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($this->header('Accept') ?? ''), 'application/json');
    }

    public function expectsJson(): bool
    {
        return $this->isAjax() || str_starts_with($this->path, '/api/');
    }

    /**
     * @param array<string, string> $params
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function routeParam(string $key, ?string $default = null): ?string
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * @return array<string, string>
     */
    public function routeParams(): array
    {
        return $this->routeParams;
    }

    /**
     * @param array<string, mixed> $rules
     * @param array<string, string> $messages
     */
    public function validate(array $rules, array $messages = []): Validator
    {
        $validator = new Validator($this->all(), $rules, $messages);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        return $validator;
    }
}