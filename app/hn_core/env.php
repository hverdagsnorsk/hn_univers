<?php
declare(strict_types=1);

/**
 * Global ENV helper
 */

if (!function_exists('env')) {

    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}