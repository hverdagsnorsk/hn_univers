<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';

/**
 * GET /hn_admin/api/tasks.php?text_id=3
 * Returnerer kun status='approved' (kan overstyres med ?status=draft hvis du vil i admin)
 */

function json_out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$textId = (int)($_GET['text_id'] ?? 0);
$status = (string)($_GET['status'] ?? 'approved');

if ($textId <= 0) json_out(['error' => 'Mangler/ugyldig text_id'], 400);
if (!in_array($status, ['approved', 'draft'], true)) $status = 'approved';

/** @var PDO $pdo */
$pdo = db();
if (!$pdo instanceof PDO) json_out(['error' => 'PDO ikke tilgjengelig (bootstrap.php)'], 500);

/* Hent oppgaver */
$stmt = db()->prepare("
    SELECT
      t.id,
      t.task_type,
      tt.label,
      tt.auto_correctable,
      t.payload_json,
      t.difficulty,
      t.section,
      t.tags
    FROM tasks t
    LEFT JOIN task_types tt ON tt.type_key = t.task_type
    WHERE t.text_id = :text_id
      AND t.status = :status
    ORDER BY t.id ASC
");
$stmt->execute([':text_id' => $textId, ':status' => $status]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$out = [];

foreach ($rows as $r) {
    $payload = json_decode((string)$r['payload_json'], true);
    if (!is_array($payload)) $payload = [];

    $out[] = [
        'id'              => (int)$r['id'],
        'task_type'       => (string)$r['task_type'],
        'label'           => (string)($r['label'] ?? $r['task_type']),
        'auto_correctable'=> (int)($r['auto_correctable'] ?? 0),
        'difficulty'      => $r['difficulty'] === null ? null : (int)$r['difficulty'],
        'section'         => $r['section'],
        'tags'            => $r['tags'],
        'payload'         => $payload,
    ];
}

json_out(['text_id' => $textId, 'status' => $status, 'tasks' => $out]);
