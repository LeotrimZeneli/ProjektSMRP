<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\ApiException;
use App\Support\Validator;
use DateTimeImmutable;

final class JobRequest
{
    public static function validate(array $input): array
    {
        $data = [
            'title' => Validator::requiredString($input, 'title', 150),
            'description' => Validator::requiredString($input, 'description', 10000),
            'department' => Validator::requiredString($input, 'department', 100),
            'location' => Validator::requiredString($input, 'location', 100),
            'deadline' => Validator::requiredString($input, 'deadline', 10),
            'status' => Validator::oneOf(
                $input['status'] ?? 'open',
                ['open', 'closed', 'draft'],
                'Invalid job status.'
            ),
        ];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['deadline']);
        if ($date === false || $date->format('Y-m-d') !== $data['deadline']) {
            throw new ApiException(422, 'Deadline must use YYYY-MM-DD format.');
        }

        return $data;
    }
}