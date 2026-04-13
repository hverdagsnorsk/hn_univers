<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/hn_core/inc/bootstrap.php';

use HnBooks\Service\TextService;

header('Content-Type: application/json');

try {
    $service = new TextService();

    $path = $service->save($_POST);

    echo json_encode([
        'success' => true,
        'path' => $path
    ]);

} catch (Throwable $e) {
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}