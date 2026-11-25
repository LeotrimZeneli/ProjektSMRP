<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ApplicationRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function list(?int $applicantId = null): array
    {
        $sql = 'SELECT a.id, a.user_id, a.job_id, a.cover_letter, a.status, a.applied_at,
                       u.full_name AS applicant_name, u.email AS applicant_email, j.title AS job_title
                FROM applications a
                JOIN users u ON u.id = a.user_id
                JOIN jobs j ON j.id = a.job_id';
        $params = [];
        if ($applicantId !== null) {
            $sql .= ' WHERE a.user_id = :user_id';
            $params['user_id'] = $applicantId;
        }
        $sql .= ' ORDER BY a.applied_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function create(int $userId, int $jobId, string $cvPath, ?string $coverLetter): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO applications (user_id, job_id, cv_file_path, cover_letter)
             VALUES (:user_id, :job_id, :cv_file_path, :cover_letter)'
        );
        $statement->execute([
            'user_id' => $userId,
            'job_id' => $jobId,
            'cv_file_path' => $cvPath,
            'cover_letter' => $coverLetter,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare('UPDATE applications SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);

        return $statement->rowCount() > 0 || $this->exists($id);
    }

    public function exists(int $id): bool
    {
        $statement = $this->db->prepare('SELECT id FROM applications WHERE id = :id');
        $statement->execute(['id' => $id]);

        return (bool) $statement->fetch();
    }

    public function cvPath(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT user_id, cv_file_path FROM applications WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return $row ?: null;
    }
}
