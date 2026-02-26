<?php

namespace WCDO\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function put(string $path, callable $handler): void
    {
        $this->routes[] = ['PUT', $path, $handler];
    }

    public function delete(string $path, callable $handler): void
    {
        $this->routes[] = ['DELETE', $path, $handler];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        // Preflight CORS
        if ($method === 'OPTIONS') {
            http_response_code(204);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            exit;
        }

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($routePath, $uri);
            if ($params !== null) {
                $handler($params);
                return;
            }
        }

        Response::notFound("Route [{$method}] {$uri} introuvable");
    }

    /** Transforme /api/produits/{id} en regex et extrait les params */
    private function match(string $routePath, string $uri): ?array
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
