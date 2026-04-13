<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LexStorageService;

header('Content-Type: application/json; charset=utf-8');

$pdo = DatabaseManager::get('lex');
$storage = new LexStorageService($pdo);

$id = (int)($_POST['id'] ?? 0);
$payloadRaw = $_POST['payload'] ?? '';

$payload = json_decode($payloadRaw, true);

if (!$id || !is_array($payload)) {
    echo json_encode(['ok' => false, 'error' => 'invalid input'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $entryId = $storage->storeStructured($payload);

    $stmt = $pdo->prepare("
        UPDATE lex_generation_queue
        SET status = 'done', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    echo json_encode([
        'ok' => true,
        'entry_id' => $entryId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    $stmt = $pdo->prepare("
        UPDATE lex_generation_queue
        SET status = 'failed', error_message = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$e->getMessage(), $id]);

    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}