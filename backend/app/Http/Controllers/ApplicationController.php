<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\ApiException;
use App\Core\Request;
use App\Core\Response;
use App\Http\Middleware\RoleMiddleware;
use App\Services\ApplicationService;

final class ApplicationController
{
    public function __construct(private readonly ApplicationService $applications)
    {
    }

    public function index(array $user): never
    {
        Response::json(['data' => $this->applications->list($user)]);
    }

    public function store(Request $request, array $user): never
    {
        RoleMiddleware::assertRole($user, ['applicant']);
        $id = $this->applications->create(
            $request->input(),
            $request->file('cv'),
            (int) $user['id']
        );
        Response::json(['message' => 'Application submitted.', 'id' => $id], 201);
    }

    public function updateStatus(int $id, Request $request, array $user): never
    {
        RoleMiddleware::assertRole($user, ['hr', 'admin']);
        $this->applications->updateStatus($id, $request->input());
        Response::json(['message' => 'Application status updated.']);
    }

    public function downloadCv(int $id, array $user): never
    {
        $application = $this->applications->cvPath($id);
        if (!in_array($user['role'], ['hr', 'admin'], true) &&
            (int) $application['user_id'] !== (int) $user['id']) {
            throw new ApiException(403, 'You do not have permission to access this CV.');
        }

        $storageDirectory = realpath(dirname(__DIR__, 3) . '/storage/uploads/cvs');
        $path = realpath(dirname(__DIR__, 3) . '/' . $application['cv_file_path']);
        if ($storageDirectory === false || $path === false ||
            !str_starts_with($path, $storageDirectory . DIRECTORY_SEPARATOR) ||
            !is_file($path) || !is_readable($path)) {
            throw new ApiException(404, 'CV file not found on the server.');
        }
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $types = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        if (!isset($types[$extension])) {
            throw new ApiException(404, 'CV file not found on the server.');
        }
        header('Content-Type: ' . $types[$extension]);
        header('Content-Disposition: attachment; filename="cv-' . $id . '.' . $extension . '"');
        header('Content-Length: ' . (string) filesize($path));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }
}
