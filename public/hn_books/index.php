<?php
declare(strict_types=1);

$appPath = realpath(__DIR__ . '/../../app/hn_books');

require_once $appPath . '/../hn_core/inc/bootstrap.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($requestUri, PHP_URL_PATH);

/* ==========================================================
   NORMALISER
========================================================== */

if (str_starts_with($uri, '/hn_books')) {
    $uri = substr($uri, strlen('/hn_books'));
}

$uri = trim($uri, '/');

/* ==========================================================
   ROOT → LISTE
========================================================== */

if ($uri === '') {
    require $appPath . '/index.php';
    exit;
}

/* ==========================================================
   🔥 BOOK ROUTING
========================================================== */

$bookPath = $appPath . '/books/' . $uri . '/index.php';

if (is_file($bookPath)) {
    require $bookPath;
    exit;
}

/* ==========================================================
   API
========================================================== */

if (str_starts_with($uri, 'api/')) {

    $apiPath = realpath($appPath . '/' . $uri);

    if ($apiPath && str_starts_with($apiPath, $appPath) && is_file($apiPath)) {
        require $apiPath;
        exit;
    }

    http_response_code(404);
    exit;
}

/* ==========================================================
   404
========================================================== */

http_response_code(404);
echo 'Not found';