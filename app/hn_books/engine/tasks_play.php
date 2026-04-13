<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/bootstrap.php';

$pdo = $GLOBALS['pdo'] ?? null;

if (!$pdo instanceof PDO) {
    exit('PDO mangler.');
}

$setId = (int)($_GET['set_id'] ?? 0);

if ($setId <= 0) {
    exit('Ugyldig set_id');
}

/* --------------------------------------------------
HENT OPPGAVER
-------------------------------------------------- */

$stmt = $pdo->prepare("
SELECT
    t.id,
    t.task_type,
    t.difficulty,
    t.payload_json
FROM task_set_items i
JOIN tasks t
  ON t.id = i.task_id
WHERE i.task_set_id = :sid
AND t.status IN ('approved','draft','generated')
ORDER BY i.sort_order
");

$stmt->execute(['sid'=>$setId]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(!$rows){
    exit('Ingen oppgaver funnet.');
}

/* --------------------------------------------------
BYGG TASKS ARRAY
-------------------------------------------------- */

$tasks = [];

foreach($rows as $r){

    $p = json_decode($r['payload_json'],true);

    if(!$p) continue;

    $p['id'] = (int)$r['id'];
    $p['difficulty'] = (int)$r['difficulty'];
    $p['type'] = $r['task_type'];

    $tasks[] = $p;
}

?>
<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">
<title>Oppgaver</title>

<link rel="stylesheet" href="tasks/tasks.css">

</head>

<body>

<div class="player">

<div id="progress"></div>

<div id="question"></div>

<div id="options"></div>

<button id="nextBtn" style="display:none">
Neste
</button>

</div>

<script>

window.TASKS = <?= json_encode($tasks,JSON_UNESCAPED_UNICODE) ?>;

window.ATTEMPT_ID = null;

</script>

<script src="/hn_books/task_system/js/task_player.js"></script>

</body>
</html>