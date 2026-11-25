<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Http\Middleware\RoleMiddleware;
use App\Services\UserService;

final class AdminUserController
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(array $user): never
    {
        RoleMiddleware::assertRole($user, ['admin']);
        Response::json(['data' => $this->users->list()]);
    }

    public function updateRole(int $id, Request $request, array $user): never
    {
        RoleMiddleware::assertRole($user, ['admin']);
        $this->users->updateRole($id, $request->input(), (int) $user['id']);
        Response::json(['message' => 'User role updated.']);
    }
}
