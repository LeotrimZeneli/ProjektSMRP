<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Http\Middleware\RoleMiddleware;
use App\Services\InterviewService;

final class InterviewController
{
    public function __construct(private readonly InterviewService $interviews)
    {
    }

    public function index(array $user): never
    {
        Response::json(['data' => $this->interviews->list($user)]);
    }

    public function store(Request $request, array $user): never
    {
        RoleMiddleware::assertRole($user, ['hr', 'admin']);
        $id = $this->interviews->create($request->input(), (int) $user['id']);
        Response::json(['message' => 'Interview scheduled.', 'id' => $id], 201);
    }
}
