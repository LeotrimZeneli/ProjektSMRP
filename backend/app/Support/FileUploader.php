<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\ApiException;
use RuntimeException;

final class FileUploader
{
    private const MAX_SIZE = 5 * 1024 * 1024;
    private const TYPES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    public function store(?array $file): array
    {
        if ($file === null || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new ApiException(422, 'A CV file in PDF, DOC, or DOCX format is required.');
        }
        $temporaryPath = $file['tmp_name'] ?? '';
        if (!is_string($temporaryPath) || $temporaryPath === '' ||
            !isset($file['size']) || (int) $file['size'] < 1 ||
            (int) $file['size'] > self::MAX_SIZE ||
            !is_uploaded_file($temporaryPath)) {
            throw new ApiException(422, 'The CV is too large or the upload is invalid (maximum 5 MB).');
        }

        $actualSize = filesize($temporaryPath);
        if ($actualSize === false || $actualSize < 1 || $actualSize > self::MAX_SIZE) {
            throw new ApiException(422, 'The CV is too large or the upload is invalid (maximum 5 MB).');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($temporaryPath);
        if (!is_string($mime) || !isset(self::TYPES[$mime])) {
            throw new ApiException(422, 'The CV must be a PDF, DOC, or DOCX file.');
        }

        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' .
            DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'cvs';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not create the CV upload directory.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $absolutePath)) {
            throw new RuntimeException('Could not save the uploaded CV.');
        }

        return [
            'absolute_path' => $absolutePath,
            'relative_path' => 'storage/uploads/cvs/' . $filename,
        ];
    }

    public function delete(string $path): void
    {
        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Could not remove an orphaned CV upload.');
        }
    }
}
