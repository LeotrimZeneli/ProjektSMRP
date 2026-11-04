<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\JobRepository;
use DateTimeImmutable;

final class JobService
{
    public function __construct(private readonly JobRepository $jobs)
    {
    }

    public function list(bool $includeClosed, ?string $status): array
    {
        if ($status !== null && !in_array($status, ['open', 'closed', 'draft'], true)) {
            throw new ApiException(422, 'Invalid job status filter.');
        }

        return $this->jobs->list($includeClosed, $status);
    }

    public function create(array $input, int $creatorId): int
    {
        $data = [
            'title' => AuthService::requiredString($input, 'title', 150),
            'description' => AuthService::requiredString($input, 'description', 10000),
            'department' => AuthService::requiredString($input, 'department', 100),
            'location' => AuthService::requiredString($input, 'location', 100),
            'deadline' => AuthService::requiredString($input, 'deadline', 10),
            'status' => $input['status'] ?? 'open',
        ];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['deadline']);
        if ($date === false || $date->format('Y-m-d') !== $data['deadline']) {
            throw new ApiException(422, 'Deadline must use YYYY-MM-DD format.');
        }
        if (!is_string($data['status']) || !in_array($data['status'], ['open', 'closed', 'draft'], true)) {
            throw new ApiException(422, 'Invalid job status.');
        }

        return $this->jobs->create($data, $creatorId);
    }
}
