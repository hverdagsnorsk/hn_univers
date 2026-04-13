<?php
declare(strict_types=1);

/*
|-------------------------------------------------------
| HN LEX LOOKUP API
|-------------------------------------------------------
*/

require_once __DIR__ . '/../../../app/hn_core/inc/bootstrap.php';

use HnLex\Controller\LookupController;

/* ======================================================
   DEBUG
====================================================== */

file_put_contents(
    '/home/7/h/hverdagsnorsk/www/logs/lookup_debug.log',
    "[LOOKUP HIT]\n",
    FILE_APPEND
);

/* ======================================================
   RUN CONTROLLER
====================================================== */

try {

    $controller = new LookupController();

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/lookup_debug.log',
        "[CONTROLLER CREATED]\n",
        FILE_APPEND
    );

    $controller->handle();

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/lookup_debug.log',
        "[HANDLE CALLED]\n",
        FILE_APPEND
    );

} catch (Throwable $e) {

    file_put_contents(
        '/home/7/h/hverdagsnorsk/www/logs/lookup_debug.log',
        "[FATAL ERROR] " . $e->getMessage() . "\n",
        FILE_APPEND
    );

    http_response_code(500);

    echo json_encode([
        'found' => false,
        'error' => $e->getMessage()
    ]);
}