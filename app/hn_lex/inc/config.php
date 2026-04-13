<?php
declare(strict_types=1);

/*
  hn_lex/inc/config.php
  Leser .env fra hn_translate
*/

$envFile = dirname(__DIR__, 2) . ''/../.env';';

if (!is_file($envFile)) {
    http_response_code(500);
    exit('Fant ikke felles .env (hn_translate/.env)');
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;

    $pos = strpos($line, '=');
    if ($pos === false) continue;

    $key = trim(substr($line, 0, $pos));
    $val = trim(substr($line, $pos + 1));

    if (
        (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
        (str_starts_with($val, "'") && str_ends_with($val, "'"))
    ) {
        $val = substr($val, 1, -1);
    }

    if ($key !== '' && !isset($_ENV[$key])) {
        $_ENV[$key] = $val;
    }
}

// DB
define('DB_HOST', $_ENV['DB_HOST'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// App
define('APP_ENV', $_ENV['APP_ENV'] ?? 'prod');
define('APP_DEBUG', (int)($_ENV['APP_DEBUG'] ?? 0));
