<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Router mínim: registre per mètode + ruta i despatx amb suport de paràmetres
 * dinàmics tipus /accounts/{id}.
 */
final class Router
{
    /** @var array<string,array<string,callable|array>> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[strtoupper($method)][$this->normalize($path)] = $handler;
    }

    private function normalize(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function dispatch(?string $method = null, ?string $uri = null): void
    {
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = $uri ?? ($_SERVER['REQUEST_URI'] ?? '/');
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $params = $this->match($route, $path);
            if ($params !== null) {
                $this->invoke($handler, $params);
                return;
            }
        }

        http_response_code(404);
        View::render('errors/404', [], 'layouts/app');
    }

    /** @return array<string,string>|null */
    private function match(string $route, string $path): ?array
    {
        if (!str_contains($route, '{')) {
            return $route === $path ? [] : null;
        }
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route);
        if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return null;
    }

    /** @param array<string,string> $params */
    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $handler = [new $class(), $method];
        }
        call_user_func($handler, $params);
    }
}
