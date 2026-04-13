<?php
declare(strict_types=1);

namespace HN\Core;

use PDO;
use RuntimeException;

final class App
{
    private static array $container = [];

    public static function set(string $key, mixed $value): void
    {
        self::$container[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$container)) {
            throw new RuntimeException("Missing container key: {$key}");
        }

        return self::$container[$key];
    }

    public static function dbMain(): PDO
    {
        return self::get('db.main');
    }

    public static function dbLex(): PDO
    {
        return self::get('db.lex');
    }

    public static function dbCourses(): PDO
    {
        return self::get('db.courses');
    }
}