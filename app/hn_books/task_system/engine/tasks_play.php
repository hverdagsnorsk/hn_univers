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
AND t.status IN ('approved','draft')
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
<title>Oppgaver – Hverdagsnorsk</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

/* =====================================================
HN DESIGN TOKENS
===================================================== */

:root{

--hn-primary:#3C6464;
--hn-secondary:#2F8485;
--hn-light:#8CBCBC;
--hn-bg:#F6F9F9;
--hn-border:#D0D3D3;
--hn-text:#2c2c2c;

}

body{

margin:0;
font-family:system-ui,-apple-system,Segoe UI,Roboto;
background:var(--hn-bg);
color:var(--hn-text);

}

/* =====================================================
HEADER
===================================================== */

.header{

background:var(--hn-primary);
color:white;
padding:22px;
text-align:center;
font-size:20px;
font-weight:600;

}

/* =====================================================
PLAYER
===================================================== */

.player{

max-width:800px;
margin:auto;
padding:30px;

}

/* =====================================================
PROGRESS
===================================================== */

#progress{

margin-bottom:20px;
font-size:14px;
color:#666;

}

/* =====================================================
TASK CARD
===================================================== */

.task-card{

background:white;
border-radius:10px;
padding:30px;
box-shadow:0 4px 12px rgba(0,0,0,0.06);
border:1px solid var(--hn-border);

}

/* =====================================================
QUESTION
===================================================== */

#question{

font-size:20px;
font-weight:600;
margin-bottom:16px;

}

/* =====================================================
SENTENCE
===================================================== */

.sentence{

font-size:18px;
margin-bottom:20px;
padding:14px;
background:#f0f5f5;
border-radius:6px;

}

/* =====================================================
OPTIONS
===================================================== */

#options{

display:flex;
flex-direction:column;
gap:12px;

}

#options button{

background:white;
border:2px solid var(--hn-light);
padding:14px;
border-radius:8px;
font-size:16px;
cursor:pointer;
transition:all .2s;

}

#options button:hover{

background:var(--hn-light);
color:white;

}

/* =====================================================
INPUT
===================================================== */

#options input{

padding:12px;
font-size:16px;
border:2px solid var(--hn-border);
border-radius:6px;

}

/* =====================================================
NEXT BUTTON
===================================================== */

#nextBtn{

margin-top:22px;
padding:14px 26px;
font-size:16px;
background:var(--hn-secondary);
border:none;
color:white;
border-radius:8px;
cursor:pointer;

}

#nextBtn:hover{

background:var(--hn-primary);

}

</style>

</head>

<body>

<div class="header">
Oppgaver
</div>

<div class="player">

<div id="progress"></div>

<div class="task-card">

<div id="question"></div>

<div id="options"></div>

<button id="nextBtn" style="display:none">
Neste oppgave
</button>

</div>

</div>

<script>

window.TASKS = <?= json_encode($tasks,JSON_UNESCAPED_UNICODE) ?>;

</script>

<script src="/hn_books/task_system/js/task_player.js"></script>

</body>
</html>