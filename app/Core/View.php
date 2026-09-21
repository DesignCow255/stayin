<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Plain-PHP view renderer with layout inheritance and a small set of directives.
 * No template dependency: views are PHP files under resources/views.
 */
final class View
{
    private static string $root = '';
    /** @var array<string, mixed> */
    private static array $shared = [];
    /** @var array<string, string> */
    private static array $sections = [];
    private static ?string $currentSection = null;
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

    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): Response
    {
        $instance = new self();
        $instance->data = [...self::$shared, ...$data];
        $html = $instance->capture($view);
        if ($layout !== null) {
            // Allow views to manage their own 'content' section via View::start/stop,
            // or fall back to the captured output.
            if (!isset(self::$sections['content'])) {
                self::$sections['content'] = $html;
            }
            $html = $instance->capture($layout);
        }
        return Response::html($html);
    }

    /**
     * Render without any layout (fragments, emails, print views).
     * @param array<string, mixed> $data
     */
    public static function fragment(string $view, array $data = []): string
    {
        $instance = new self();
        $instance->data = [...self::$shared, ...$data];
        return $instance->capture($view);
    }

    /** @param array<string, mixed> $data */
    public static function component(string $view, array $data = []): string
    {
        return self::fragment('components/' . $view, $data);
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $view, array $data = []): string
    {
        return self::fragment($view, $data);
    }

    /** Start a content section for layout capture. */
    public static function start(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    /** Stop capturing a content section and store it. */
    public static function stop(): string
    {
        if (self::$currentSection !== null) {
            self::$sections[self::$currentSection] = (string) ob_get_clean();
            self::$currentSection = null;
        }
        return '';
    }

    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    /** @return array<string, string> */
    public static function sections(): array
    {
        return self::$sections;
    }

    public static function hasSection(string $name): bool
    {
        return isset(self::$sections[$name]) && self::$sections[$name] !== '';
    }

    public static function exists(string $view): bool
    {
        return self::resolve($view) !== null;
    }

    /** @param array<string, mixed> $data */
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

    private static function resolve(string $view): ?string
    {
        $view = str_replace(['..', "\0"], '', str_replace('.', '/', $view));
        $file = self::$root . '/' . ltrim($view, '/') . '.php';
        return is_file($file) ? $file : null;
    }
}
