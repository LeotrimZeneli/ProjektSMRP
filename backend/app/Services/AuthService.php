<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Http\Requests\LoginRequest;
use App\Repositories\UserRepository;
use App\Support\JWT;
use App\Support\Validator;
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
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new ApiException(422, 'Password must be between 8 and 72 bytes long.');
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
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
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
        $credentials = LoginRequest::validate($input);

        $user = $this->users->findByEmail($credentials['email']);
        if ($user === null || !password_verify($credentials['password'], $user['password'])) {
            throw new ApiException(401, 'Email or password is incorrect.');
        }

        return ['user' => UserRepository::publicFields($user), 'token' => JWT::issue($user)];
    }

    public static function requiredString(array $input, string $key, int $maxLength): string
    {
        return Validator::requiredString($input, $key, $maxLength);
    }

    public static function email(mixed $value): string
    {
        return Validator::email($value);
    }
}
