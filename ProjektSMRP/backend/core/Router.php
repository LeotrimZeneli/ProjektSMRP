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
        $pathMatched = false;
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path(), $matches) !== 1) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] === $request->method()) {
                array_shift($matches);
                ($route['handler'])($request, ...array_map('urldecode', $matches));
                return;
            }
        }

        if ($pathMatched) {
            throw new ApiException(405, 'Method not allowed.');
        }
        throw new ApiException(404, 'Endpoint not found.');
    }
}
