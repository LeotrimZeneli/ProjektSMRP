<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Http\Middleware\RoleMiddleware;
use App\Services\JobService;

final class JobController
{
    public function __construct(private readonly JobService $jobs)
    {
    }

    public function index(Request $request, ?array $user): never
    {
        $staff = $user !== null && in_array($user['role'], RoleMiddleware::STAFF_ROLES, true);
        Response::json(['data' => $this->jobs->list($staff, $staff ? $request->query('status') : null)]);
    }

    public function store(Request $request, array $user): never
    {
        RoleMiddleware::assertRole($user, RoleMiddleware::JOB_MANAGEMENT_ROLES);
        $id = $this->jobs->create($request->input(), (int) $user['id']);
        Response::json(['message' => 'Job created.', 'id' => $id], 201);
    }
}
