<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LookupService;
use HnLex\Service\SenseGenerationService;
use HnLex\Service\LexStorageService;

header('Content-Type: application/json');

try {

    $input = json_decode(file_get_contents('php://input'), true);

    if (!is_array($input)) {
        throw new RuntimeException('Invalid JSON');
    }

    $word = trim((string)($input['word'] ?? ''));
    $sentence = trim((string)($input['sentence'] ?? ''));
    $override = $input['override'] ?? null;

    if ($word === '') {
        throw new RuntimeException('Missing word');
    }

    $pdo = DatabaseManager::get('lex');

    /* ======================================================
       FULL KONSTRUKSJON (KORREKT)
    ====================================================== */

    $storage = new LexStorageService($pdo);

    $senseGenerator = new SenseGenerationService(
        $pdo,
        $storage,
        null
    );

    $lookup = new LookupService(
        $pdo,
        $senseGenerator,
        null
    );

    /* ======================================================
       LOOKUP
    ====================================================== */

    $result = $lookup->lookup(
        $word,
        'nb',
        'A1',
        [
            'sentence' => $sentence,
            'override_payload' => $override
        ]
    );

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(400);

    echo json_encode([
        'error' => $e->getMessage()
    ]);
}