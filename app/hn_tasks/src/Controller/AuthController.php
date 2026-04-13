<?php
declare(strict_types=1);

namespace HnTasks\Controller;

use PDO;

final class AuthController
{
    public function __construct(private PDO $db) {}

    public function register(string $name, string $email, string $password): array
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO hn_users (name, email, password, created_at)
            VALUES (:name, :email, :password, NOW())
        ");

        try {
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hash
            ]);
        } catch (\Throwable $e) {
            return ['error' => 'E-post finnes fra før'];
        }

        return ['status' => 'ok'];
    }

    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM hn_users WHERE email = :email LIMIT 1
        ");

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['error' => 'Feil e-post eller passord'];
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ];

        $this->db->prepare("
            UPDATE hn_users SET last_login = NOW() WHERE id = :id
        ")->execute(['id' => $user['id']]);

        return ['status' => 'ok', 'user' => $_SESSION['user']];
    }

    public function logout(): void
    {
        session_destroy();
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}