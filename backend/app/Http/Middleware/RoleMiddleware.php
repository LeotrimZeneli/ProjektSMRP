<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\ApiException;

final class RoleMiddleware
{
    public const STAFF_ROLES = ['admin', 'hr', 'manager', 'reviewer'];
    public const JOB_MANAGEMENT_ROLES = ['admin', 'hr', 'manager'];

    public static function assertRole(array $user, array $roles): void
    {
        if (!in_array($user['role'], $roles, true)) {
            throw new ApiException(403, 'You do not have permission to perform this action.');
        }
    }
}
