<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\JobController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Repositories\ApplicationRepository;
use App\Repositories\InterviewRepository;
use App\Repositories\JobRepository;
use App\Repositories\UserRepository;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\InterviewService;
use App\Services\JobService;
use App\Support\FileUploader;

ini_set('display_errors', '0');
header('Cache-Control: no-store');

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'App\\Core\\' => __DIR__ . '/core/',
        'App\\' => __DIR__ . '/app/',
    ];
    foreach ($prefixes as $prefix => $directory) {
        if (str_starts_with($class, $prefix)) {
            $file = $directory . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require_once __DIR__ . '/config/database.php';

CorsMiddleware::apply();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$request = new Request();

try {
    if ($request->method() === 'GET' && $request->path() === '/api/v1/health') {
        Response::json(['status' => 'ok']);
    }

    $services = null;
    $getServices = static function () use (&$services): array {
        if ($services === null) {
            $db = database();
            $users = new UserRepository($db);
            $jobs = new JobRepository($db);
            $applications = new ApplicationRepository($db);
            $interviews = new InterviewRepository($db);
            $services = [
                'authMiddleware' => new AuthMiddleware($users),
                'authController' => new AuthController(new AuthService($users)),
                'jobController' => new JobController(new JobService($jobs)),
                'applicationController' => new ApplicationController(
                    new ApplicationService($applications, $jobs, new FileUploader())
                ),
                'interviewController' => new InterviewController(new InterviewService($interviews)),
            ];
        }

        return $services;
    };
    $router = new Router();
    $router->add('POST', '/api/v1/auth/register', static function (Request $request) use ($getServices): never {
        $getServices()['authController']->register($request);
    });
    $router->add('POST', '/api/v1/auth/login', static function (Request $request) use ($getServices): never {
        $getServices()['authController']->login($request);
    });
    $router->add('GET', '/api/v1/auth/me', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['authController']->me($services['authMiddleware']->authenticate($request));
    });
    $router->add('GET', '/api/v1/jobs', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $authorization = $request->header('Authorization');
        $user = $authorization === null ? null : $services['authMiddleware']->authenticate($request);
        $services['jobController']->index($request, $user);
    });
    $router->add('POST', '/api/v1/jobs', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['jobController']->store($request, $services['authMiddleware']->authenticate($request));
    });
    $router->add('GET', '/api/v1/applications', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['applicationController']->index($services['authMiddleware']->authenticate($request));
    });
    $router->add('POST', '/api/v1/applications', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['applicationController']->store($request, $services['authMiddleware']->authenticate($request));
    });
    $router->add('PATCH', '/api/v1/applications/([0-9]+)/status', static function (Request $request, string $id) use ($getServices): never {
        $services = $getServices();
        $services['applicationController']->updateStatus(
            (int) $id,
            $request,
            $services['authMiddleware']->authenticate($request)
        );
    });
    $router->add('GET', '/api/v1/applications/([0-9]+)/cv', static function (Request $request, string $id) use ($getServices): never {
        $services = $getServices();
        $services['applicationController']->downloadCv(
            (int) $id,
            $services['authMiddleware']->authenticate($request)
        );
    });
    $router->add('GET', '/api/v1/interviews', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['interviewController']->index($services['authMiddleware']->authenticate($request));
    });
    $router->add('POST', '/api/v1/interviews', static function (Request $request) use ($getServices): never {
        $services = $getServices();
        $services['interviewController']->store($request, $services['authMiddleware']->authenticate($request));
    });

    $router->dispatch($request);
} catch (ApiException $exception) {
    Response::json(['error' => $exception->getMessage()], $exception->status);
} catch (PDOException $exception) {
    error_log('SMRP database error: ' . $exception->getMessage());
    Response::json(['error' => 'Database connection failed. Check MySQL and database configuration.'], 500);
} catch (Throwable $exception) {
    error_log('SMRP API error: ' . $exception->getMessage());
    Response::json(['error' => 'Internal server error. Check the Apache/PHP error log.'], 500);
}
