<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Http\Middleware\RoleMiddleware;
use App\Repositories\ApplicationRepository;
use App\Repositories\JobRepository;
use App\Support\FileUploader;
use App\Support\Validator;
use PDOException;

final class ApplicationService
{
    private const STATUSES = ['pending', 'reviewing', 'interview', 'accepted', 'rejected'];

    public function __construct(
        private readonly ApplicationRepository $applications,
        private readonly JobRepository $jobs,
        private readonly FileUploader $uploader
    ) {
    }

    public function list(array $user): array
    {
        return $this->applications->list(
            in_array($user['role'], RoleMiddleware::STAFF_ROLES, true) ? null : (int) $user['id']
        );
    }

    public function create(array $input, ?array $file, int $userId): int
    {
        $jobId = Validator::positiveInt($input['job_id'] ?? null, 'job_id');
        if ($this->jobs->findOpen($jobId) === null) {
            throw new ApiException(404, 'The job is not currently accepting applications.');
        }
        $coverLetter = $input['cover_letter'] ?? '';
        if (!is_string($coverLetter) || mb_strlen($coverLetter) > 10000) {
            throw new ApiException(422, 'Cover letter must not exceed 10000 characters.');
        }

        $stored = $this->uploader->store($file);
        try {
            return $this->applications->create(
                $userId,
                $jobId,
                $stored['relative_path'],
                trim($coverLetter) !== '' ? trim($coverLetter) : null
            );
        } catch (PDOException $exception) {
            $this->uploader->delete($stored['absolute_path']);
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw new ApiException(409, 'An application for this job already exists.');
            }
            throw $exception;
        } catch (\Throwable $exception) {
            $this->uploader->delete($stored['absolute_path']);
            throw $exception;
        }
    }

    public function updateStatus(int $id, array $input): void
    {
        $status = $input['status'] ?? null;
        if (!is_string($status) || !in_array($status, self::STATUSES, true)) {
            throw new ApiException(422, 'A valid application status is required.');
        }
        if (!$this->applications->updateStatus($id, $status)) {
            throw new ApiException(404, 'Application not found.');
        }
    }

    public function cvPath(int $id): array
    {
        $application = $this->applications->cvPath($id);
        if ($application === null) {
            throw new ApiException(404, 'Application or CV not found.');
        }

        return $application;
    }
}
