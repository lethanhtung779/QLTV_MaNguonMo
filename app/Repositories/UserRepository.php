<?php
declare(strict_types=1);

namespace Library\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id AND is_active = 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        $statement = $this->db->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        $statement->execute(['id' => $id, 'name' => $name, 'email' => $email]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = :password WHERE id = :id');
        $statement->execute(['id' => $id, 'password' => $hash]);
    }

    public function markLogin(int $id): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
