<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Renders a PHP template inside the shared layout.
 */
final class View
{
    public function __construct(private readonly string $viewPath)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], string $layout = 'layout'): string
    {
        $content = $this->capture($template, $data);
        return $this->capture($layout, $data + ['content' => $content]);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $template, array $data): string
    {
        $file = $this->viewPath . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    /** Escapes output for HTML. Every value printed in a view goes through this. */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
