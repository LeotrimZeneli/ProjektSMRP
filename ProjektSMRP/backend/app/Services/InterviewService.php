<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\ApiException;
use App\Repositories\InterviewRepository;
use DateTimeImmutable;
use PDOException;

final class InterviewService
{
    public function __construct(private readonly InterviewRepository $interviews)
    {
    }

    public function list(array $user): array
    {
        return $this->interviews->list(
            in_array($user['role'], ['admin', 'hr'], true) ? null : (int) $user['id']
        );
    }

    public function create(array $input, int $interviewerId): int
    {
        $applicationId = filter_var($input['application_id'] ?? null, FILTER_VALIDATE_INT);
        if ($applicationId === false || $applicationId === null || $applicationId < 1 ||
            !$this->interviews->applicationExists($applicationId)) {
            throw new ApiException(404, 'Application not found.');
        }
        $scheduledAt = AuthService::requiredString($input, 'scheduled_at', 16);
        $location = AuthService::requiredString($input, 'location_link', 255);
        $notes = $input['notes'] ?? '';
        if (!is_string($notes) || mb_strlen($notes) > 10000) {
            throw new ApiException(422, 'Interview notes must not exceed 10000 characters.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduledAt);
        if ($date === false || $date->format('Y-m-d\TH:i') !== $scheduledAt) {
            throw new ApiException(422, 'scheduled_at must use YYYY-MM-DDTHH:MM format.');
        }

        try {
            return $this->interviews->create($applicationId, $interviewerId, [
                'scheduled_at' => $date->format('Y-m-d H:i:s'),
                'location_link' => $location,
                'notes' => trim($notes) !== '' ? trim($notes) : null,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ApiException(409, 'This application already has a scheduled interview.');
            }
            throw $exception;
        }
    }
}
