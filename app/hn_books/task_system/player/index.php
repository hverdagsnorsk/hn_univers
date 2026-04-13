<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">
<title>Oppgaver</title>

<link rel="stylesheet" href="/hn_books/task_system/css/task_player.css">

<script>

const TASKS = <?= json_encode($tasks ?? []) ?>;
const ATTEMPT_ID = <?= json_encode($attempt_id ?? null) ?>;

</script>

<script src="/hn_books/task_system/js/task_player.js" defer></script>

</head>

<body>

<div class="player">

<div id="progress"></div>

<div id="question"></div>

<div id="sentence"></div>

<div id="options"></div>

<button id="nextBtn" style="display:none">Neste</button>

</div>

</body>
</html>