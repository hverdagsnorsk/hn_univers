<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

header('Content-Type: application/json');

/* ==========================================================
   Input (JSON body)
========================================================== */

$data = json_decode(file_get_contents('php://input'), true);

$id    = (int)($data['id'] ?? 0);
$field = $data['field'] ?? '';
$value = trim((string)($data['value'] ?? ''));

/* ==========================================================
   Validation
========================================================== */

$allowedFields = ['explanation', 'example'];

if (!$id || !in_array($field, $allowedFields, true)) {
    echo json_encode(['success' => false]);
    exit;
}

/* ==========================================================
   Update
========================================================== */

$stmt = $pdo_lex->prepare("
    UPDATE lex_explanations
    SET {$field} = ?
    WHERE id = ?
");

$stmt->execute([$value, $id]);

echo json_encode(['success' => true]);