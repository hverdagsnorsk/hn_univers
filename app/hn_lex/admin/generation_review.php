<?php
declare(strict_types=1);

session_start();

if (empty($_SESSION['admin'])) {
    header('Location: /hn_admin/login.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'].'/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

$stmt = $pdo->query("
    SELECT id, word, language, level, status, created_at
    FROM lex_generation_queue
    WHERE status = 'pending'
    ORDER BY created_at DESC
    LIMIT 30
");

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Lex Generation Review</title>

<style>
body { font-family: Arial; background:#f5f5f5; padding:20px; }
.card { background:white; padding:20px; margin-bottom:15px; border-radius:8px; }
textarea { width:100%; height:200px; font-family: monospace; }
button { padding:10px 15px; margin-top:10px; border:none; border-radius:5px; cursor:pointer; }
.approve { background:green; color:white; }
.reject { background:red; color:white; }
.load { background:#333; color:white; }
.preview { background:#fafafa; padding:10px; margin-top:10px; border-radius:5px; }
</style>

<script>
async function loadAI(word, textareaId, previewId) {
    const res = await fetch(`/hn_lex/api/admin/generate.php?word=${encodeURIComponent(word)}`);
    const json = await res.json();

    if (!json.ok) {
        alert("AI-feil: " + json.error);
        return;
    }

    const txt = JSON.stringify(json.data, null, 2);
    document.getElementById(textareaId).value = txt;

    updatePreview(textareaId, previewId);
}

function updatePreview(textareaId, previewId) {
    try {
        const data = JSON.parse(document.getElementById(textareaId).value);

        let html = '';
        html += '<b>Lemma:</b> ' + (data.lemma || '-') + '<br>';
        html += '<b>Ordklasse:</b> ' + (data.word_class || '-') + '<br>';

        if (data.grammar) {
            for (const k in data.grammar) {
                html += k + ': ' + data.grammar[k] + '<br>';
            }
        }

        if (data.senses && data.senses.length) {
            html += '<b>Forklaring:</b><br>' + data.senses[0].definition;
        }

        document.getElementById(previewId).innerHTML = html;

    } catch {
        document.getElementById(previewId).innerHTML = 'Ugyldig JSON';
    }
}
</script>

</head>
<body>

<h1>Generation Queue</h1>

<?php foreach ($items as $item): 
    $id = (int)$item['id'];
    $word = $item['word'];
    $textareaId = "payload_$id";
    $previewId = "preview_$id";
?>

<div class="card">

<h3><?= h($word) ?></h3>

<button class="load" onclick="loadAI('<?= h($word) ?>','<?= $textareaId ?>','<?= $previewId ?>')">
Hent AI
</button>

<form method="post" action="/hn_lex/api/admin/generation_save.php">

<input type="hidden" name="id" value="<?= $id ?>">

<textarea id="<?= $textareaId ?>" name="payload" onkeyup="updatePreview('<?= $textareaId ?>','<?= $previewId ?>')"></textarea>

<div class="preview" id="<?= $previewId ?>"></div>

<button type="submit" class="approve">Godkjenn</button>

</form>

<form method="post" action="/hn_lex/api/admin/generation_reject.php">
<input type="hidden" name="id" value="<?= $id ?>">
<button type="submit" class="reject">Avvis</button>
</form>

</div>

<?php endforeach; ?>

</body>
</html>