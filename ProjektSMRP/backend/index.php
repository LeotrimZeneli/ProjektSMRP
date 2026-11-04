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

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
$configuredOrigin = getenv('APP_ALLOWED_ORIGIN');
if (is_string($configuredOrigin) && $configuredOrigin !== '') {
    $allowedOrigins[] = $configuredOrigin;
}
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
    header('Access-Control-Allow-Headers: Authorization, Content-Type');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, OPTIONS');
}
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$request = new Request();

try {
    $db = database();
    $users = new UserRepository($db);
    $jobs = new JobRepository($db);
    $applications = new ApplicationRepository($db);
    $interviews = new InterviewRepository($db);
    $authMiddleware = new AuthMiddleware($users);

    $authController = new AuthController(new AuthService($users));
    $jobController = new JobController(new JobService($jobs));
    $applicationController = new ApplicationController(
        new ApplicationService($applications, $jobs, new FileUploader())
    );
    $interviewController = new InterviewController(new InterviewService($interviews));

    $router = new Router();
    $router->add('GET', '/api/v1/health', static function (Request $request): never {
        Response::json(['status' => 'ok']);
    });
    $router->add('POST', '/api/v1/auth/register', [$authController, 'register']);
    $router->add('POST', '/api/v1/auth/login', [$authController, 'login']);
    $router->add('GET', '/api/v1/auth/me', static function (Request $request) use ($authMiddleware, $authController): never {
        $authController->me($authMiddleware->authenticate($request));
    });
    $router->add('GET', '/api/v1/jobs', static function (Request $request) use ($authMiddleware, $jobController): never {
        $authorization = $request->header('Authorization');
        $user = $authorization === null ? null : $authMiddleware->authenticate($request);
        $jobController->index($request, $user);
    });
    $router->add('POST', '/api/v1/jobs', static function (Request $request) use ($authMiddleware, $jobController): never {
        $jobController->store($request, $authMiddleware->authenticate($request));
    });
    $router->add('GET', '/api/v1/applications', static function (Request $request) use ($authMiddleware, $applicationController): never {
        $applicationController->index($authMiddleware->authenticate($request));
    });
    $router->add('POST', '/api/v1/applications', static function (Request $request) use ($authMiddleware, $applicationController): never {
        $applicationController->store($request, $authMiddleware->authenticate($request));
    });
    $router->add('PATCH', '/api/v1/applications/([0-9]+)/status', static function (Request $request, string $id) use ($authMiddleware, $applicationController): never {
        $applicationController->updateStatus((int) $id, $request, $authMiddleware->authenticate($request));
    });
    $router->add('GET', '/api/v1/applications/([0-9]+)/cv', static function (Request $request, string $id) use ($authMiddleware, $applicationController): never {
        $applicationController->downloadCv((int) $id, $authMiddleware->authenticate($request));
    });
    $router->add('GET', '/api/v1/interviews', static function (Request $request) use ($authMiddleware, $interviewController): never {
        $interviewController->index($authMiddleware->authenticate($request));
    });
    $router->add('POST', '/api/v1/interviews', static function (Request $request) use ($authMiddleware, $interviewController): never {
        $interviewController->store($request, $authMiddleware->authenticate($request));
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
