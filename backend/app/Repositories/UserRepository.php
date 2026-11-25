<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ApiException;
use PDO;
use Throwable;

final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, full_name, email, password, role, phone_number FROM users WHERE email = :email'
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, full_name, email, role, phone_number FROM users WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function listAll(): array
    {
        return $this->db->query(
            'SELECT id, full_name, email, role, created_at
             FROM users
             ORDER BY created_at DESC, id DESC'
        )->fetchAll();
    }

    public function updateRole(int $id, string $role, int $actorId): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $lockClause = $driver === 'mysql' ? ' FOR UPDATE' : '';
        $this->db->beginTransaction();

        try {
            $statement = $this->db->prepare('SELECT role FROM users WHERE id = :id' . $lockClause);
            $statement->execute(['id' => $id]);
            $currentRole = $statement->fetchColumn();
            if ($currentRole === false) {
                throw new ApiException(404, 'User not found.');
            }

            if ($actorId === $id && $currentRole === 'admin' && $role !== 'admin') {
                throw new ApiException(409, 'You cannot remove your own administrator role.');
            }

            if ($currentRole === 'admin' && $role !== 'admin') {
                $admins = $this->db->query(
                    "SELECT id FROM users WHERE role = 'admin' ORDER BY id" . $lockClause
                )->fetchAll();
                if (count($admins) <= 1) {
                    throw new ApiException(409, 'The last administrator cannot be demoted.');
                }
            }

            $statement = $this->db->prepare('UPDATE users SET role = :role WHERE id = :id');
            $statement->execute(['role' => $role, 'id' => $id]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function create(string $name, string $email, string $passwordHash, ?string $phone): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (full_name, email, password, phone_number) VALUES (:name, :email, :password, :phone)'
        );
        $statement->execute([
            'name' => $name,
            'email' => $email,
            'password' => $passwordHash,
            'phone' => $phone,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public static function publicFields(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone_number' => $user['phone_number'] ?? null,
        ];
    }
}
