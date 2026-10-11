<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Maps HTTP method + path to a controller action.
 *
 * Supports simple named parameters, e.g. "/tickets/{id}/claim".
 */
final class Router
{
    /** @var array<string, array<int, array{pattern: string, params: string[], handler: callable}>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        preg_match_all('/\{(\w+)\}/', $path, $matches);
        $pattern = '#^' . preg_replace('/\{\w+\}/', '([^/]+)', rtrim($path, '/') ?: '/') . '$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'params'  => $matches[1],
            'handler' => $handler,
        ];
    }

    /**
     * Finds the matching route and runs it.
     *
     * @return array{status: int, body: string}
     */
    public function dispatch(string $method, string $uri): array
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?? '/', '/') ?: '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['pattern'], $path, $values)) {
                array_shift($values);
                $args = array_combine($route['params'], $values) ?: [];
                return ['status' => 200, 'body' => (string) ($route['handler'])(...$args)];
            }
        }

        if ($this->pathExistsForOtherMethod($method, $path)) {
            return ['status' => 405, 'body' => 'Method not allowed'];
        }

        return ['status' => 404, 'body' => 'Page not found'];
    }

    private function pathExistsForOtherMethod(string $method, string $path): bool
    {
        foreach ($this->routes as $routeMethod => $routes) {
            if ($routeMethod === $method) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $path)) {
                    return true;
                }
            }
        }
        return false;
    }
}
