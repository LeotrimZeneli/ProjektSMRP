<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\ApiException;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Support\JWT;

final class AuthMiddleware
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    public function authenticate(Request $request): array
    {
        $authorization = $request->header('Authorization') ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches) !== 1) {
            throw new ApiException(401, 'A valid bearer token is required.');
        }

        $claims = JWT::verify($matches[1]);
        $subject = filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT);
        if ($subject === false || $subject === null || $subject < 1) {
            throw new ApiException(401, 'Invalid or expired bearer token.');
        }
        $user = $this->users->findById($subject);
        if ($user === null) {
            throw new ApiException(401, 'The token user no longer exists.');
        }

        return $user;
    }
}
