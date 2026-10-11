<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Shared helpers for controllers: rendering, redirects, flash messages and CSRF checks.
 */
abstract class Controller
{
    public function __construct(protected readonly View $view)
    {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = []): string
    {
        return $this->view->render($template, $data + [
            'csrf'  => Csrf::token(),
            'flash' => $this->takeFlash(),
        ]);
    }

    protected function redirect(string $to): string
    {
        header('Location: ' . $to, true, 303);
        return '';
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'] = ['type' => $type, 'message' => $message];
    }

    /** @return array{type: string, message: string}|null */
    private function takeFlash(): ?array
    {
        $flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_flash']);
        return $flash;
    }

    protected function requireCsrf(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Your session expired. Go back, refresh the page and try again.');
        }
    }
}
