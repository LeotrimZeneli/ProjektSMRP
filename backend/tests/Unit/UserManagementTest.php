<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Http\Middleware\RoleMiddleware;
use App\Repositories\UserRepository;
use App\Services\UserService;

spl_autoload_register(static function (string $class): void {
    $backend = dirname(__DIR__, 2);
    $prefixes = [
        'App\\Core\\' => $backend . '/core/',
        'App\\' => $backend . '/app/',
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

function assertUserManagementSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true));
    }
}

function assertUserManagementStatus(int $status, callable $operation): void
{
    try {
        $operation();
    } catch (ApiException $exception) {
        assertUserManagementSame($status, $exception->status, 'Unexpected API status');
        return;
    }

    throw new RuntimeException('Expected an API exception with status ' . $status);
}

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('PDO SQLite is required to run UserManagementTest.');
}

$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec(
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'applicant',
        phone_number TEXT NULL,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
);
$insert = $db->prepare('INSERT INTO users (full_name, email, password, role) VALUES (:name, :email, :password, :role)');
$insert->execute(['name' => 'First Admin', 'email' => 'first@example.test', 'password' => 'secret', 'role' => 'admin']);
$firstAdminId = (int) $db->lastInsertId();
$insert->execute(['name' => 'Second Admin', 'email' => 'second@example.test', 'password' => 'secret', 'role' => 'admin']);
$secondAdminId = (int) $db->lastInsertId();
$insert->execute(['name' => 'Applicant', 'email' => 'applicant@example.test', 'password' => 'secret', 'role' => 'applicant']);
$applicantId = (int) $db->lastInsertId();

$repository = new UserRepository($db);
$users = new UserService($repository);
assertUserManagementSame(3, count($users->list()), 'User listing should include all accounts');
assertUserManagementSame(false, array_key_exists('password', $users->list()[0]), 'User listing must not expose password hashes');

$users->updateRole($applicantId, ['role' => 'reviewer'], $firstAdminId);
assertUserManagementSame('reviewer', $repository->findById($applicantId)['role'], 'Admin should be able to assign reviewer access');
assertUserManagementStatus(403, static fn() => RoleMiddleware::assertRole(
    ['role' => 'reviewer'],
    RoleMiddleware::JOB_MANAGEMENT_ROLES
));
RoleMiddleware::assertRole(['role' => 'reviewer'], RoleMiddleware::STAFF_ROLES);
$users->updateRole($secondAdminId, ['role' => 'hr'], $firstAdminId);
assertUserManagementSame('hr', $repository->findById($secondAdminId)['role'], 'Admin should be able to demote another administrator while one remains');

assertUserManagementStatus(409, static fn() => $users->updateRole($firstAdminId, ['role' => 'hr'], $firstAdminId));
assertUserManagementStatus(409, static fn() => $users->updateRole($firstAdminId, ['role' => 'hr'], $secondAdminId));
assertUserManagementSame('admin', $repository->findById($firstAdminId)['role'], 'Failed administrator demotion must roll back');
assertUserManagementStatus(422, static fn() => $users->updateRole($applicantId, ['role' => 'owner'], $firstAdminId));
assertUserManagementStatus(404, static fn() => $users->updateRole(999, ['role' => 'hr'], $firstAdminId));
assertUserManagementStatus(403, static fn() => RoleMiddleware::assertRole(['role' => 'manager'], ['admin']));

echo "UserManagementTest passed.\n";
