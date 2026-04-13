<?php
declare(strict_types=1);

namespace HnCore\Database;

use PDO;
use RuntimeException;

final class DatabaseManager
{
    private static array $connections = [];

    public static function get(string $name): PDO
    {
        $name = strtoupper($name);

        if (isset(self::$connections[$name])) {
            return self::$connections[$name];
        }

        $host = $_ENV["DB_{$name}_HOST"] ?? null;
        $db   = $_ENV["DB_{$name}_NAME"] ?? null;
        $user = $_ENV["DB_{$name}_USER"] ?? null;
        $pass = $_ENV["DB_{$name}_PASS"] ?? null;

        if (!$host || !$db) {
            throw new RuntimeException("Missing DB config for {$name}");
        }

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        // Debug (valgfritt, men nyttig)
        // error_log("[DB] Connected to {$name}");

        return self::$connections[$name] = $pdo;
    }
}