<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../engine/bootstrap.php';

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error"=>"Invalid JSON"]);
    exit;
}

$attemptId = (int)($input["attempt"] ?? 0);
$taskId    = (int)($input["task"] ?? 0);
$correct   = !empty($input["correct"]) ? 1 : 0;

if (!$attemptId || !$taskId) {
    http_response_code(400);
    echo json_encode(["error"=>"Missing parameters"]);
    exit;
}

/*
--------------------------------------------------
Hent tekst + bruker
--------------------------------------------------
*/

$stmt = $pdo->prepare("
SELECT
a.participant_email,
t.book_key,
t.text_key
FROM attempts a
JOIN texts t ON t.id = a.text_id
WHERE a.id = ?
");

$stmt->execute([$attemptId]);

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(["error"=>"Attempt not found"]);
    exit;
}

$email   = $row["participant_email"];
$bookKey = $row["book_key"];
$textKey = $row["text_key"];

/*
--------------------------------------------------
Lagre response
--------------------------------------------------
*/

$stmt = $pdo->prepare("
INSERT INTO responses
(attempt_id,task_id,is_correct)
VALUES (?,?,?)
");

$stmt->execute([
    $attemptId,
    $taskId,
    $correct
]);

/*
--------------------------------------------------
Hent task_type + difficulty
--------------------------------------------------
*/

$stmt = $pdo->prepare("
SELECT task_type,difficulty
FROM tasks
WHERE id = ?
");

$stmt->execute([$taskId]);

$task = $stmt->fetch(PDO::FETCH_ASSOC);

$taskType   = $task["task_type"] ?? "unknown";
$difficulty = $task["difficulty"] ?? 1;

/*
--------------------------------------------------
Lagre adaptive data
--------------------------------------------------
*/

$stmt = $pdo->prepare("
INSERT INTO task_attempts
(user_id,book_key,text_key,task_type,difficulty,correct)
VALUES (NULL,?,?,?,?,?)
");

$stmt->execute([
    $bookKey,
    $textKey,
    $taskType,
    $difficulty,
    $correct
]);

echo json_encode([
    "status"=>"ok",
    "correct"=>$correct
]);