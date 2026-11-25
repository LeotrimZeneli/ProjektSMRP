<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Router;

require dirname(__DIR__, 2) . '/core/ApiException.php';
require dirname(__DIR__, 2) . '/core/Request.php';
require dirname(__DIR__, 2) . '/core/Router.php';

function assertFeatureSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true));
    }
}

function assertFeatureStatus(int $status, callable $operation): void
{
    try {
        $operation();
    } catch (ApiException $exception) {
        assertFeatureSame($status, $exception->status, 'Unexpected route status');
        return;
    }

    throw new RuntimeException('Expected route status ' . $status);
}

$router = new Router();
$dispatchedId = null;
$router->add('GET', '/api/v1/jobs/([0-9]+)', static function (Request $request, string $id) use (&$dispatchedId): void {
    $dispatchedId = $id;
});

$_SERVER['SCRIPT_NAME'] = '/backend/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/backend/api/v1/jobs/42';
$router->dispatch(new Request());
assertFeatureSame('42', $dispatchedId, 'Base path stripping or route parameter dispatch failed');

$_SERVER['REQUEST_METHOD'] = 'POST';
assertFeatureStatus(405, static fn() => $router->dispatch(new Request()));

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/backend/api/v1/unknown';
assertFeatureStatus(404, static fn() => $router->dispatch(new Request()));

echo "JobFlowTest passed.\n";