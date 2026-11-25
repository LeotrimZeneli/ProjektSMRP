<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class JobRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function list(bool $includeClosed, ?string $status = null): array
    {
        $sql = 'SELECT j.id, j.title, j.description, j.department, j.location, j.status, j.created_by,
                       j.deadline, j.created_at, u.full_name AS creator_name
                FROM jobs j JOIN users u ON u.id = j.created_by';
        $conditions = [];
        $params = [];

        if (!$includeClosed) {
            $conditions[] = "j.status = 'open' AND j.deadline >= CURDATE()";
        } elseif ($status !== null) {
            $conditions[] = 'j.status = :status';
            $params['status'] = $status;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY j.created_at DESC';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function create(array $data, int $creatorId): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO jobs (title, description, department, location, status, created_by, deadline)
             VALUES (:title, :description, :department, :location, :status, :created_by, :deadline)'
        );
        $statement->execute([
            'title' => $data['title'],
            'description' => $data['description'],
            'department' => $data['department'],
            'location' => $data['location'],
            'status' => $data['status'],
            'created_by' => $creatorId,
            'deadline' => $data['deadline'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function findOpen(int $id): ?array
    {
        $statement = $this->db->prepare(
            "SELECT id, title FROM jobs WHERE id = :id AND status = 'open' AND deadline >= CURDATE()"
        );
        $statement->execute(['id' => $id]);
        $job = $statement->fetch();

        return $job ?: null;
    }
}
