<?php
declare(strict_types=1);

$appPath = realpath(__DIR__ . '/../../app/hn_books');

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($requestUri, PHP_URL_PATH);

/* ==========================================================
   NORMALISER URI
========================================================== */

if (str_starts_with($uri, '/hn_books')) {
    $uri = substr($uri, strlen('/hn_books'));
}

if ($uri === '' || $uri === false) {
    $uri = '/';
}

/* ==========================================================
   🔥 VIKTIG: IKKE HÅNDTER ASSETS HER
   (la webserver gjøre jobben)
========================================================== */

if (str_starts_with($uri, '/assets/')) {
    http_response_code(404);
    exit;
}

/* ==========================================================
   API
========================================================== */

if (str_starts_with($uri, '/api/')) {

    $apiPath = realpath($appPath . $uri);

    if ($apiPath && str_starts_with($apiPath, $appPath) && is_file($apiPath)) {
        require $apiPath;
        exit;
    }

    http_response_code(404);
    exit;
}

/* ==========================================================
   STATIC / PHP (kun innen app/hn_books)
========================================================== */

$filePath = realpath($appPath . $uri);

if ($filePath && str_starts_with($filePath, $appPath) && is_file($filePath)) {

    $ext = pathinfo($filePath, PATHINFO_EXTENSION);

    if ($ext === 'php') {
        require $filePath;
        exit;
    }

    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'html' => 'text/html',
    ];

    if (isset($mimeTypes[$ext])) {
        header('Content-Type: ' . $mimeTypes[$ext]);
    }

    readfile($filePath);
    exit;
}

/* ==========================================================
   FALLBACK → APP INDEX
========================================================== */

require $appPath . '/index.php';