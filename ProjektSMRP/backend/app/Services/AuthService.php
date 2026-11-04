<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\UserRepository;
use App\Support\JWT;
use PDOException;

final class AuthService
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function register(array $input): array
    {
        $name = self::requiredString($input, 'full_name', 100);
        $email = self::email($input['email'] ?? null);
        $password = $input['password'] ?? null;
        $phone = $input['phone_number'] ?? '';
        if (!is_string($password) || strlen($password) < 8) {
            throw new ApiException(422, 'Password must be at least 8 characters long.');
        }
        if (!is_string($phone) || mb_strlen(trim($phone)) > 30) {
            throw new ApiException(422, 'Phone number must not exceed 30 characters.');
        }

        try {
            $id = $this->users->create(
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
                trim($phone) !== '' ? trim($phone) : null
            );
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ApiException(409, 'An account with this email already exists.');
            }
            throw $exception;
        }

        $user = $this->users->findById($id);
        if ($user === null) {
            throw new \RuntimeException('The newly created user could not be loaded.');
        }

        return ['user' => UserRepository::publicFields($user), 'token' => JWT::issue($user)];
    }

    public function login(array $input): array
    {
        $email = self::email($input['email'] ?? null);
        $password = $input['password'] ?? null;
        if (!is_string($password)) {
            throw new ApiException(422, 'Email or password is invalid.');
        }

        $user = $this->users->findByEmail($email);
        if ($user === null || !password_verify($password, $user['password'])) {
            throw new ApiException(401, 'Email or password is incorrect.');
        }

        return ['user' => UserRepository::publicFields($user), 'token' => JWT::issue($user)];
    }

    public static function requiredString(array $input, string $key, int $maxLength): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $maxLength) {
            throw new ApiException(422, $key . ' is required and must not exceed ' . $maxLength . ' characters.');
        }

        return trim($value);
    }

    public static function email(mixed $value): string
    {
        if (!is_string($value) || strlen(trim($value)) > 150 ||
            filter_var(trim($value), FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException(422, 'A valid email address is required.');
        }

        return trim($value);
    }
}
