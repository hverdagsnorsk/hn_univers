<?php
declare(strict_types=1);

require_once __DIR__ . '/../../engine/bootstrap.php';
require_once __DIR__ . '/../../engine/V8AdaptiveSelector.php';

$attemptId = (int)($_GET['attempt'] ?? 0);

if (!$attemptId) {
    die("Ugyldig attempt-id");
}

/*
--------------------------------------------------
Hent attempt + tekst
--------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        a.id,
        a.participant_email,
        a.participant_name,
        t.id AS text_id,
        t.book_key,
        t.text_key
    FROM attempts a
    JOIN texts t ON t.id = a.text_id
    WHERE a.id = ?
    LIMIT 1
");

$stmt->execute([$attemptId]);

$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    die("Forsøk ikke funnet");
}

/*
--------------------------------------------------
Hent adaptive oppgaver
--------------------------------------------------
*/

$tasks = V8AdaptiveSelector::selectTasks(
    $pdo,
    (int)$attempt['text_id'],
    (string)$attempt['participant_email'],
    5
);

/*
--------------------------------------------------
Hvis ingen oppgaver finnes -> avslutt
--------------------------------------------------
*/

if (!$tasks || count($tasks) === 0) {

    $stmt = $pdo->prepare("
        UPDATE attempts
        SET finished_at = NOW()
        WHERE id = ?
        AND finished_at IS NULL
    ");

    $stmt->execute([$attemptId]);

    header("Location: result.php?attempt=" . $attemptId);
    exit;
}

/*
--------------------------------------------------
Konverter payload_json til struktur JS kan bruke
--------------------------------------------------
*/

foreach ($tasks as &$t) {

    $payload = json_decode($t["payload_json"], true);

    $t["payload"] = $payload ?? [];

}

unset($t);

?>
<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">
<link rel="stylesheet" href="../css/task.css">

<script>

const TASKS = <?= json_encode($tasks, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>;

const ATTEMPT_ID = <?= $attemptId ?>;

</script>

<script src="../js/task_player.js" defer></script>

</head>

<body>

<div class="player">

<div id="progress"></div>

<div id="question"></div>

<div id="options"></div>

<button id="nextBtn" style="display:none">Neste</button>

</div>

</body>
</html>