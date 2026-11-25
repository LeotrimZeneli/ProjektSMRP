<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $allowedMethods = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path(), $matches) !== 1) {
                continue;
            }
            $allowedMethods[] = $route['method'];
            if ($route['method'] === $request->method()) {
                array_shift($matches);
                ($route['handler'])($request, ...array_map('urldecode', $matches));
                return;
            }
        }

        if ($allowedMethods !== []) {
            header('Allow: ' . implode(', ', array_unique($allowedMethods)));
            throw new ApiException(405, 'Method not allowed.');
        }
        throw new ApiException(404, 'Endpoint not found.');
    }
}
