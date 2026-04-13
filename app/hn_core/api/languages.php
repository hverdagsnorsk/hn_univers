<?php
declare(strict_types=1);

require_once __DIR__ . '/../../inc/bootstrap.php';
require_once __DIR__ . '/../languages.php';

header('Content-Type: application/json; charset=utf-8');

// Kun lesing – ingen login påkrevd
try {
    $languages = get_active_languages($pdo);

    echo json_encode([
        'status' => 'ok',
        'count'  => count($languages),
        'languages' => array_map(static function ($l) {
            return [
                'code'     => $l['code'],
                'name'     => $l['name'],
                'synonyms' => $l['synonyms']
            ];
        }, $languages)
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Kunne ikke hente språk'
    ]);
}
