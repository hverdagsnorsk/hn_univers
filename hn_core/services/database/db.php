<?php
declare(strict_types=1);

class DB
{
    private static array $connections = [];

    public static function set(string $key, PDO $pdo): void
    {
        self::$connections[$key] = $pdo;
    }

    public static function get(string $key): PDO
    {
        if (!isset(self::$connections[$key])) {
            throw new RuntimeException("Database '$key' not registered");
        }

        return self::$connections[$key];
    }

    public static function exists(string $key): bool
    {
        return isset(self::$connections[$key]);
    }
}

function db(string $key='main'): PDO
{
    return DB::get($key);
}

function db_exists(string $key): bool
{
    return DB::exists($key);
}

function hn_pdo(string $host,string $db,string $user,string $pass): PDO
{
    return new PDO(
        "mysql:host={$host};dbname={$db};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}
