<?php

declare(strict_types=1);

$configuredOrigins = getenv('APP_ALLOWED_ORIGINS') ?: 'http://localhost:5173,http://127.0.0.1:5173';
$allowedOrigins = array_values(array_filter(array_map('trim', explode(',', $configuredOrigins))));
$singleOrigin = getenv('APP_ALLOWED_ORIGIN');
if (is_string($singleOrigin) && $singleOrigin !== '') {
    $allowedOrigins[] = trim($singleOrigin);
}

return [
    'environment' => getenv('APP_ENV') ?: 'production',
    'allowed_origins' => array_values(array_unique($allowedOrigins)),
];