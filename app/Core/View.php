<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP view renderer with layout inheritance and a small set of directives.
 *
 * No template dependency: views are PHP files under resources/views.
 */
final class View
{
    private static string $root = '';
    /** @var array<string, mixed> */
    private static array $shared = [];
    /** @var array<int, string> */
    private static array $stack = [];
    /** @var array<string, string> */
    private static array $sections = [];
    private static ?string $currentSection = null;
    private static ?string $layout = null;
    /** @var array<string, mixed> */
    private array $data = [];

    public static function boot(string $root): void
    {
        self::$root = rtrim($root, '/');
    }

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): Response
    {
        $instance = new self();
        $instance->data = [...self::$shared, ...$data];

        $html = $instance->capture($view);

        if ($layout !== null) {
            self::$sections['content'] = $html;
            $html = $instance->capture($layout);
        }

        return Response::html($html);
    }

    /**
     * Render without any layout (fragments, emails, print views).
     *
     * @param array<string, mixed> $data
     */
    public static function fragment(string $view, array $data = []): string
    {
        $instance = new self();
        $instance->data = [...self::$shared, ...$data];

        return $instance->capture($view);
    }

    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && self::$sections[$name] !== '';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function capture(string $view, array $data = []): string
    {
        if ($data !== []) {
            $this->data = [...$this->data, ...$data];
        }

        $file = self::resolve($view);
        if ($file === null) {
            throw new \RuntimeException('View not found: ' . $view);
        }

        extract($this->data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }

    public static function exists(string $view): bool
    {
        return self::resolve($view) !== null;
    }

    private static function resolve(string $view): ?string
    {
        $view = str_replace(['..', "\0"], '', str_replace('.', '/', $view));
        $file = self::$root . '/' . ltrim($view, '/') . '.php';
        return is_file($file) ? $file : null;
    }

    public function e(mixed $value): string
    {
        return e($value);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function component(string $view, array $data = []): void
    {
        echo $this->capture('components/' . $view, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function partial(string $view, array $data = []): void
    {
        echo $this->capture($view, $data);
    }

    public function slotStart(): void
    {
        ob_start();
    }

    public function slotEnd(): string
    {
        return (string) ob_get_clean();
    }
}