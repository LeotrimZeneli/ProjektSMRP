<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Support\Validator;

final class UserService
{
    private const ROLES = ['applicant', 'reviewer', 'hr', 'manager', 'admin'];

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function list(): array
    {
        return $this->users->listAll();
    }

    public function updateRole(int $id, array $input, int $actorId): void
    {
        $role = Validator::oneOf($input['role'] ?? null, self::ROLES, 'A valid user role is required.');
        $this->users->updateRole($id, $role, $actorId);
    }
}
