<?php
namespace App\Core;

class Router {
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler): void {
        $this->routes[] = ['method' => strtoupper($method), 'path' => $path, 'handler' => $handler];
    }

    public function get(string $path, callable|array $handler): void {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable|array $handler): void {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(string $method, string $uri): void {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        $method = strtoupper($method);
        $allowed = [];

        foreach ($this->routes as $route) {
            $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $route['path']) . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed[] = $route['method'];
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }

            $handler = $route['handler'];
            if (is_array($handler) && is_string($handler[0])) {
                $handler = [new $handler[0](), $handler[1]];
            }

            call_user_func_array($handler, $params);
            return;
        }

        if ($allowed !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', array_unique($allowed)));
            View::render('errors/405', ['title' => 'Método no permitido']);
            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'Página no encontrada']);
    }
}
