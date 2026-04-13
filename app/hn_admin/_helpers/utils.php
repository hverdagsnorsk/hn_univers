<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| _helpers/utils.php
| Generelle hjelpefunksjoner
|------------------------------------------------------------
*/

/**
 * Send JSON-respons og avslutt
 */
function json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Sikker HTML-escaping
 */
function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Les POST-verdi trygt
 */
function post(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Les GET-verdi trygt
 */
function get(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

/**
 * Enkel debug-logg (til fil)
 */
function debug_log(string $message, mixed $context = null): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if ($context !== null) {
        $line .= ' | ' . json_encode(
            $context,
            JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        );
    }

    $line .= PHP_EOL;

    file_put_contents(
        __DIR__ . '/../_logs/debug.log',
        $line,
        FILE_APPEND
    );
}
