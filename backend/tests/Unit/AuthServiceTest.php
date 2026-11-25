<?php

declare(strict_types=1);

use App\Core\ApiException;
use App\Http\Requests\JobRequest;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\JWT;
use App\Support\Validator;

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

function assertSameValue(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message . ': expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true));
    }
}

function assertApiStatus(int $status, callable $operation): void
{
    try {
        $operation();
    } catch (ApiException $exception) {
        assertSameValue($status, $exception->status, 'Unexpected API status');
        return;
    }

    throw new RuntimeException('Expected an API exception with status ' . $status);
}

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('PDO SQLite is required to run AuthServiceTest.');
}

putenv('JWT_SECRET=unit-test-secret-with-at-least-32-characters');
$db = new PDO('sqlite::memory:', options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec(
    "CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'applicant',
        phone_number TEXT NULL
    )"
);
$auth = new AuthService(new UserRepository($db));
$registered = $auth->register([
    'full_name' => 'Ada Applicant',
    'email' => 'ADA@example.test',
    'password' => 'long-enough-password',
    'phone_number' => '+123456789',
]);

assertSameValue('ada@example.test', $registered['user']['email'], 'Registration email normalization failed');
assertSameValue('applicant', $registered['user']['role'], 'Registration role should not be user-controlled');
assertSameValue('1', JWT::verify($registered['token'])['sub'], 'Issued token subject is invalid');
assertSameValue('ada@example.test', $auth->login([
    'email' => 'ADA@example.test',
    'password' => 'long-enough-password',
])['user']['email'], 'Login failed for normalized email');
assertSameValue('Trim me', Validator::requiredString(['name' => ' Trim me '], 'name', 20), 'String validation failed');
assertSameValue(12, Validator::positiveInt('12', 'job_id'), 'Integer validation failed');
assertApiStatus(422, static fn() => Validator::positiveInt(true, 'job_id'));
assertSameValue('open', JobRequest::validate([
    'title' => 'Engineer',
    'description' => 'Build systems',
    'department' => 'Engineering',
    'location' => 'Remote',
    'deadline' => '2027-01-31',
])['status'], 'Job defaults are invalid');
assertApiStatus(422, static fn() => JobRequest::validate([
    'title' => 'Engineer',
    'description' => 'Build systems',
    'department' => 'Engineering',
    'location' => 'Remote',
    'deadline' => '2027-02-31',
]));
assertApiStatus(422, static fn() => $auth->register([
    'full_name' => 'Short',
    'email' => 'short@example.test',
    'password' => 'short',
]));
assertApiStatus(401, static fn() => $auth->login([
    'email' => 'ada@example.test',
    'password' => 'incorrect-password',
]));

echo "AuthServiceTest passed.\n";