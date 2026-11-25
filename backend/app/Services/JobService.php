<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Http\Requests\JobRequest;
use App\Repositories\JobRepository;

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
        return $this->jobs->create(JobRequest::validate($input), $creatorId);
    }
}
