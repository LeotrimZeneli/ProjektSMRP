<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class InterviewRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function list(?int $applicantId = null): array
    {
        $sql = 'SELECT i.id, i.application_id, i.scheduled_at, i.location_link, i.notes,
                       a.user_id AS applicant_id, applicant.full_name AS applicant_name,
                       j.title AS job_title, interviewer.full_name AS interviewer_name
                FROM interviews i
                JOIN applications a ON a.id = i.application_id
                JOIN users applicant ON applicant.id = a.user_id
                JOIN users interviewer ON interviewer.id = i.interviewer_id
                JOIN jobs j ON j.id = a.job_id';
        $params = [];
        if ($applicantId !== null) {
            $sql .= ' WHERE a.user_id = :user_id';
            $params['user_id'] = $applicantId;
        }
        $sql .= ' ORDER BY i.scheduled_at ASC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function applicationExists(int $applicationId): bool
    {
        $statement = $this->db->prepare('SELECT id FROM applications WHERE id = :id');
        $statement->execute(['id' => $applicationId]);

        return (bool) $statement->fetch();
    }

    public function create(int $applicationId, int $interviewerId, array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO interviews (application_id, interviewer_id, scheduled_at, location_link, notes)
             VALUES (:application_id, :interviewer_id, :scheduled_at, :location_link, :notes)'
        );
        $statement->execute([
            'application_id' => $applicationId,
            'interviewer_id' => $interviewerId,
            'scheduled_at' => $data['scheduled_at'],
            'location_link' => $data['location_link'],
            'notes' => $data['notes'],
        ]);

        return (int) $this->db->lastInsertId();
    }
}
