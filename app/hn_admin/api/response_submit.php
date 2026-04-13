<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function norm(string $s): string {
    $s = trim($s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/\s+/u', ' ', $s);
    return $s ?? '';
}

function safe_array($v): array {
    return is_array($v) ? $v : [];
}

/** @var PDO $pdo */
$pdo = db();
if (!$pdo instanceof PDO) json_out(['error' => 'PDO ikke tilgjengelig (bootstrap.php)'], 500);

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '[]', true);
if (!is_array($data)) json_out(['error' => 'Ugyldig JSON'], 400);

$attemptId = (int)($data['attempt_id'] ?? 0);
$taskId    = (int)($data['task_id'] ?? 0);
$response  = $data['response'] ?? null;

if ($attemptId <= 0) json_out(['error' => 'Mangler/ugyldig attempt_id'], 400);
if ($taskId <= 0) json_out(['error' => 'Mangler/ugyldig task_id'], 400);

/* Valider attempt */
$stmt = db()->prepare("SELECT id, text_id, finished_at FROM attempts WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $attemptId]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$attempt) json_out(['error' => 'Attempt finnes ikke'], 404);
if ($attempt['finished_at'] !== null) json_out(['error' => 'Attempt er avsluttet'], 409);

/* Hent task + typeinfo */
$stmt = db()->prepare("
    SELECT t.id, t.text_id, t.task_type, t.payload_json, tt.auto_correctable
    FROM tasks t
    LEFT JOIN task_types tt ON tt.type_key = t.task_type
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$task) json_out(['error' => 'Oppgave finnes ikke'], 404);

/* Sikkerhet: task må tilhøre samme text_id som attempt */
if ((int)$task['text_id'] !== (int)$attempt['text_id']) {
    json_out(['error' => 'Oppgaven tilhører en annen tekst enn attempt'], 403);
}

$taskType = (string)$task['task_type'];
$payload  = json_decode((string)$task['payload_json'], true);
if (!is_array($payload)) $payload = [];

$auto = (int)($task['auto_correctable'] ?? 0);

$isCorrect = 0;
$score     = 0.0;
$feedback  = '';

/**
 * Forventet payload-kontrakter (enkel, robust):
 *
 * mcq:
 *   prompt: string
 *   options: array<string>
 *   correct_index: int   (anbefalt)
 *   (evt correct: string)
 *
 * fill_text:
 *   prompt: string  (med ___)
 *   correct: string|array<string>
 *
 * drag_sort:
 *   prompt: string
 *   items: array<string>
 *   correct_order: array<int> (indekser som viser riktig rekkefølge)
 *
 * short:
 *   prompt: string
 *   acceptable: array<string> (valgfritt, hvis du vil autoretter)
 */

if ($auto === 1) {
    if ($taskType === 'mcq') {
        $given = $response;
        $correctIndex = $payload['correct_index'] ?? null;
        $correctValue = $payload['correct'] ?? null;

        if (is_int($given) || ctype_digit((string)$given)) {
            $gi = (int)$given;
            if (is_int($correctIndex) && $gi === (int)$correctIndex) {
                $isCorrect = 1; $score = 1.0;
            }
        } else {
            $gv = norm((string)$given);
            if (is_string($correctValue) && $gv !== '' && $gv === norm($correctValue)) {
                $isCorrect = 1; $score = 1.0;
            }
        }

        $feedback = $isCorrect ? 'Riktig.' : 'Ikke helt. Prøv igjen og se i teksten.';
    }
    elseif ($taskType === 'fill_text') {
        $given = norm((string)$response);
        $corr  = $payload['correct'] ?? '';
        $ok = false;

        if (is_string($corr)) {
            $ok = ($given !== '' && $given === norm($corr));
        } elseif (is_array($corr)) {
            foreach ($corr as $c) {
                if (is_string($c) && $given !== '' && $given === norm($c)) { $ok = true; break; }
            }
        }

        if ($ok) { $isCorrect = 1; $score = 1.0; $feedback = 'Riktig.'; }
        else     { $feedback = 'Ikke helt. Se setningen i teksten én gang til.'; }
    }
    elseif ($taskType === 'drag_sort') {
        $given = $response;

        // front-end kan sende array eller JSON-string
        if (is_string($given)) {
            $tmp = json_decode($given, true);
            if (is_array($tmp)) $given = $tmp;
        }

        $givenArr = safe_array($given);
        $correct  = safe_array($payload['correct_order'] ?? []);

        if ($givenArr && $correct && count($givenArr) === count($correct)) {
            $match = true;
            for ($i=0; $i<count($correct); $i++) {
                if ((int)$givenArr[$i] !== (int)$correct[$i]) { $match = false; break; }
            }
            if ($match) { $isCorrect = 1; $score = 1.0; $feedback = 'Riktig rekkefølge.'; }
            else { $feedback = 'Ikke helt. Se på hendelsene i teksten og prøv igjen.'; }
        } else {
            $feedback = 'Ugyldig svarformat.';
        }
    }
    elseif ($taskType === 'short') {
        // Valgfritt: autoretter hvis du legger acceptable[]
        $acc = $payload['acceptable'] ?? null;
        if (is_array($acc) && $response !== null) {
            $given = norm((string)$response);
            foreach ($acc as $a) {
                if (is_string($a) && $given !== '' && $given === norm($a)) {
                    $isCorrect = 1; $score = 1.0; break;
                }
            }
            $feedback = $isCorrect ? 'Riktig.' : 'Ikke helt. Sjekk teksten og prøv igjen.';
        } else {
            // Ikke auto
            $isCorrect = 0; $score = 0.0;
            $feedback = 'Svaret er registrert.';
        }
    }
    else {
        $feedback = 'Svaret er registrert.';
    }
} else {
    $feedback = 'Svaret er registrert.';
}

/* Lagre response */
$stmt = db()->prepare("
    INSERT INTO responses (attempt_id, task_id, response_json, score, is_correct, feedback, answered_at)
    VALUES (:aid, :tid, :rjson, :score, :ok, :fb, NOW())
");
$stmt->execute([
    ':aid'   => $attemptId,
    ':tid'   => $taskId,
    ':rjson' => json_encode($response, JSON_UNESCAPED_UNICODE),
    ':score' => $score,
    ':ok'    => $isCorrect,
    ':fb'    => $feedback,
]);

json_out([
    'attempt_id' => $attemptId,
    'task_id'    => $taskId,
    'is_correct' => (int)$isCorrect,
    'score'      => (float)$score,
    'feedback'   => $feedback,
]);
