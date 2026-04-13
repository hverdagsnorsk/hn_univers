<?php
declare(strict_types=1);

/**
 * Escape (HTML-safe)
 */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape med fallback
 */
function e_or(mixed $value, string $fallback = '-'): string
{
    $value = (string)($value ?? '');
    return $value !== '' ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $fallback;
}

/**
 * Format datetime
 */
function dt(?string $value, string $fallback = ''): string
{
    if (!$value) return $fallback;

    try {
        return (new DateTime($value))->format('d.m.Y H:i');
    } catch (Throwable) {
        return $fallback;
    }
}

/**
 * Attribute-safe
 */
function ea(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}