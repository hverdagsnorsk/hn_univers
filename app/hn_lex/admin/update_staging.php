<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

$data = json_decode(file_get_contents('php://input'), true);

$id = (int)($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT payload_json FROM lex_entries_staging WHERE id=?");
$stmt->execute([$id]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    exit;
}

$payload = json_decode($row['payload_json'], true);

$type  = $data['type'] ?? '';
$key   = $data['key'] ?? null;
$index = isset($data['index']) ? (int)$data['index'] : null;
$value = $data['value'] ?? '';

/* ================= UPDATE ================= */

if ($type === 'grammar' && $key !== null) {
    $payload['grammar'][$key] = $value;
}

if (in_array($type, ['definition','example1','example2'], true) && $index !== null) {
    $payload['senses'][$index][$type] = $value;
}

/* ================= SAVE ================= */

$stmt = $pdo->prepare("
    UPDATE lex_entries_staging
    SET payload_json = ?, created_at = NOW()
    WHERE id = ?
");

$stmt->execute([
    json_encode($payload, JSON_UNESCAPED_UNICODE),
    $id
]);

echo json_encode(['ok' => true]);