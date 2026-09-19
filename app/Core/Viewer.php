<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Native PHP template engine with layout/section support.
 *
 * Templates are plain .php files so that everything remains framework-free and
 * fully server-rendered for SEO.
 */
final class Viewer
{
    /** @var array<string, mixed> */
    private array $shared = [];
    /** @var array<string, string> */
    private array $sections = [];
    /** @var array<int, string> */
    private array $sectionStack = [];
    private string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? dirname(__DIR__, 2) . '/resources/views';
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $content = $this->renderView($view, $data);

        $layout = $data['__layout'] ?? null;
        if (is_string($layout) && $layout !== '') {
            $content = $this->renderView($layout, [...$data, '__content' => $content]);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $view, array $data): string
    {
        $file = $this->resolve($view);

        $variables = array_merge($this->shared, $data);
        unset($variables['__layout']);

        $level = ob_get_level();
        ob_start();

        try {
            (static function (string $__file, array $__variables): void {
                extract($__variables, EXTR_SKIP);
                /** @psalm-suppress UnresolvableInclude */
                require $__file;
            })($file, $variables);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }

        $output = (string) ob_get_clean();
        if (ob_get_level() > $level) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }

        return $output;
    }

    private function resolve(string $view): string
    {
        $view = str_replace(['.', '\\'], '/', $view);
        $file = $this->path . '/' . ltrim($view, '/') . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('View not found: ' . $view . ' (' . $file . ')');
        }

        return $file;
    }

    public function exists(string $view): bool
    {
        $view = str_replace(['.', '\\'], '/', $view);
        return is_file($this->path . '/' . ltrim($view, '/') . '.php');
    }

    /**
     * Render a partial and return its HTML (usable inside templates).
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $view, array $data = []): string
    {
        return $this->renderView($view, $data);
    }

    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->sectionStack);
        if ($name === null) {
            return;
        }
        $this->sections[$name] = (string) ob_get_clean();
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return isset($this->sections[$name]) && trim($this->sections[$name]) !== '';
    }

    public function push(string $name, string $value): void
    {
        $this->sections[$name] = ($this->sections[$name] ?? '') . $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function page(string $view, array $data = [], string $layout = 'layouts.public'): Response
    {
        $html = $this->render($view, [...$data, '__layout' => $layout]);

        return Response::html($html);
    }
}