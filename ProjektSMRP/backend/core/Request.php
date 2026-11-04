<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private ?array $json = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
            $path = substr($path, strlen($basePath));
        }

        return '/' . trim($path, '/');
    }

    public function query(string $key, ?string $default = null): ?string
    {
        $value = $_GET[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $_SERVER[$key] ?? null;

        if ($name === 'Authorization' && !is_string($value) && function_exists('getallheaders')) {
            foreach (getallheaders() as $header => $headerValue) {
                if (strcasecmp($header, $name) === 0 && is_string($headerValue)) {
                    return $headerValue;
                }
            }
        }

        return is_string($value) ? $value : null;
    }

    public function input(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        if ($this->json !== null) {
            return $this->json;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return $this->json = [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ApiException(400, 'Request body must be a valid JSON object.');
        }

        return $this->json = $decoded;
    }

    public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }
}
