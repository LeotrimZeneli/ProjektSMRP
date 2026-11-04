<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\UserRepository;
use App\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function register(Request $request): never
    {
        Response::json($this->auth->register($request->input()), 201);
    }

    public function login(Request $request): never
    {
        Response::json($this->auth->login($request->input()));
    }

    public function me(array $user): never
    {
        Response::json(['user' => UserRepository::publicFields($user)]);
    }
}
