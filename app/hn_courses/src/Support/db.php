<?php
declare(strict_types=1);

namespace HnCourses\Support;

use RuntimeException;

/**
 * Returnerer korrekt DB-alias for kursmodulen.
 * Finner automatisk alias basert på .env.
 */
function course_db_alias(): string
{
    foreach ($_ENV as $key => $value) {
        if (
            is_string($key)
            && is_string($value)
            && preg_match('/^DB_(.+)_NAME$/', $key, $matches) === 1
            && $value === 'hverdagsnorskn04'
        ) {
            return strtolower($matches[1]);
        }
    }

    throw new RuntimeException(
        'Fant ikke DB-alias for kursdatabasen (hverdagsnorskn04).'
    );
}

/**
 * Returnerer PDO for kursdatabasen.
 */
function course_db(): \PDO
{
    return \db(course_db_alias());
}