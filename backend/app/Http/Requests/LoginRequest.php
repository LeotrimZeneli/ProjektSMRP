<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\ApiException;
use App\Support\Validator;

final class LoginRequest
{
    public static function validate(array $input): array
    {
        $email = Validator::email($input['email'] ?? null);
        $password = $input['password'] ?? null;
        if (!is_string($password) || $password === '' || strlen($password) > 72) {
            throw new ApiException(422, 'Email or password is invalid.');
        }

        return ['email' => $email, 'password' => $password];
    }
}