<?php

declare(strict_types=1);

namespace App\Http\Middleware;

final class CorsMiddleware
{
    public static function apply(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $config = require dirname(__DIR__, 3) . '/config/app.php';
        $allowedOrigins = $config['allowed_origins'];

        if (is_string($origin) && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
        }
    }
}