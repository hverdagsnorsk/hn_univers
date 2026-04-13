<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\Ai\AiLexGenerator;

header('Content-Type: application/json; charset=utf-8');

$word  = trim((string)($_GET['word'] ?? ''));
$level = $_GET['level'] ?? 'A2';
$lang  = $_GET['lang'] ?? 'nb';

if ($word === '') {
    echo json_encode(['ok' => false, 'error' => 'missing word'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $ai = new AiLexGenerator();
    $data = $ai->generate($word, $lang, $level);

    echo json_encode([
        'ok' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}