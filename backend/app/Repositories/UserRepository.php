<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

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
