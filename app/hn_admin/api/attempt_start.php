<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';

/**
 * Forventet DB:
 * attempts(id, text_id, participant_name, participant_email, started_at, finished_at, total_score?)
 *
 * Oppretter ny attempt hvis det ikke finnes en aktiv (finished_at IS NULL)
 * for samme text_id + participant_email.
 */

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) {
    json_out(['error' => 'Ugyldig JSON'], 400);
}

$textId = (int)($data['text_id'] ?? 0);
$name   = trim((string)($data['name'] ?? ''));
$email  = trim((string)($data['email'] ?? ''));

if ($textId <= 0) json_out(['error' => 'Mangler/ugyldig text_id'], 400);
if ($name === '') json_out(['error' => 'Mangler navn'], 400);
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) json_out(['error' => 'Mangler/ugyldig e-post'], 400);

/** @var PDO $pdo */
$pdo = db();
if (!$pdo instanceof PDO) {
    json_out(['error' => 'PDO ikke tilgjengelig (bootstrap.php)'], 500);
}

/* Sjekk at teksten finnes og er aktiv */
$stmt = db()->prepare("SELECT id, active FROM texts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $textId]);
$txt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$txt) json_out(['error' => 'Tekst finnes ikke (texts.id)'], 404);
if ((int)$txt['active'] !== 1) json_out(['error' => 'Tekst er ikke aktiv'], 403);

/* Finn aktiv attempt (samme tekst + epost) */
$stmt = db()->prepare("
    SELECT id
    FROM attempts
    WHERE text_id = :text_id
      AND participant_email = :email
      AND finished_at IS NULL
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([':text_id' => $textId, ':email' => $email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    json_out(['attempt_id' => (int)$existing['id'], 'reused' => true]);
}

/* Opprett ny attempt */
$stmt = db()->prepare("
    INSERT INTO attempts (text_id, participant_name, participant_email, started_at)
    VALUES (:text_id, :name, :email, NOW())
");
$stmt->execute([':text_id' => $textId, ':name' => $name, ':email' => $email]);

json_out(['attempt_id' => (int)db()->lastInsertId(), 'reused' => false]);
