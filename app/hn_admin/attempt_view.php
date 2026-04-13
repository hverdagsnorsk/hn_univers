<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| hn_admin/attempt_view.php
| Vis enkeltbesvarelse + formattert lærerkommentar + e-postvarsel
|------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('PDO mangler.');
}

$attemptId = (int)($_GET['id'] ?? 0);
if ($attemptId <= 0) {
    exit('Ugyldig attempt-id.');
}

/* --------------------------------------------------
   Hent attempt + tekst
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT
        a.id,
        a.text_id,
        a.participant_name,
        a.participant_email,
        a.answers_json,
        a.teacher_comment,
        a.started_at,
        a.finished_at,
        a.reviewed_at,
        t.title
    FROM attempts a
    JOIN texts t ON t.id = a.text_id
    WHERE a.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $attemptId]);
$attempt = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attempt) {
    exit('Fant ikke besvarelsen.');
}

/* --------------------------------------------------
   Dekod svar
-------------------------------------------------- */
$answersJson = $attempt['answers_json'] ?? '';
$answers = $answersJson !== '' ? json_decode($answersJson, true) : [];
if (!is_array($answers)) {
    $answers = [];
}

/* --------------------------------------------------
   Hent oppgaver
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT id, task_type, payload_json
    FROM tasks
    WHERE text_id = :text_id
      AND status = 'approved'
    ORDER BY id
");
$stmt->execute(['text_id' => $attempt['text_id']]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Lagre lærerkommentar + send e-post
-------------------------------------------------- */
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $rawHtml = $_POST['teacher_comment_html'] ?? '';

    // Tillatt, enkel formattering
    $allowedTags = '<b><strong><i><em><ul><ol><li><p><br>';
    $comment = trim(strip_tags($rawHtml, $allowedTags));

    $stmt = db()->prepare("
        UPDATE attempts
        SET teacher_comment = :comment,
            reviewed_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        'comment' => $comment,
        'id'      => $attemptId
    ]);

    $attempt['teacher_comment'] = $comment;
    $attempt['reviewed_at'] = date('Y-m-d H:i:s');
    $msg = 'Kommentar lagret.';

    /* -------- E-postvarsel -------- */
    if (!empty($attempt['participant_email']) && $comment !== '') {

        $to = $attempt['participant_email'];
        $name = $attempt['participant_name'] ?: 'Deltaker';

        $subject = 'Du har fått tilbakemelding på oppgaven din';

        $body = "Hei $name,\n\n";
        $body .= "Du har nå fått tilbakemelding på oppgaven du leverte på Hverdagsnorsk.\n\n";
        $body .= "Logg inn for å lese kommentaren.\n\n";
        $body .= "Vennlig hilsen\n";
        $body .= "Svenn\nHverdagsnorsk\n";

        $headers  = "From: Hverdagsnorsk <no-reply@hverdagsnorsk.no>\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8";

        @mail($to, $subject, $body, $headers);
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Besvarelse – <?= e($attempt['participant_name']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
}

.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:30px;
}

.header img{ max-height:56px; }

h1{ margin:0; color:#2f8485; }

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:600;
}

.meta{
    color:#475569;
    font-size:.9rem;
    margin-top:6px;
}

.section{
    background:#ffffff;
    border-radius:14px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    padding:22px 26px;
    margin-bottom:30px;
}

.section h2{
    margin-top:0;
    color:#2f8485;
}

.task{
    border-top:1px solid #e5e7eb;
    padding-top:18px;
    margin-top:18px;
}

.prompt{
    font-weight:700;
    margin-bottom:8px;
}

.answer{
    background:#f8fafc;
    border-left:4px solid #2f8485;
    padding:12px 14px;
    border-radius:8px;
    white-space:pre-wrap;
}

/* ---------- Feedback editor ---------- */
.hn-editor-toolbar button{
    background:#e2e8f0;
    border:none;
    padding:6px 10px;
    border-radius:6px;
    margin-right:6px;
    cursor:pointer;
    font-weight:700;
}

.hn-editor{
    border:1px solid #cbd5e1;
    border-radius:10px;
    padding:12px 14px;
    min-height:140px;
    background:#ffffff;
}

button[type="submit"]{
    background:#2f8485;
    color:#ffffff;
    padding:10px 18px;
    border-radius:10px;
    border:none;
    font-weight:700;
    cursor:pointer;
}

.footer{
    text-align:center;
    margin-top:40px;
    font-size:.85rem;
    color:#64748b;
}
</style>
</head>

<body>

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1><?= e($attempt['title']) ?></h1>
        <a href="attempts.php">← Tilbake til innleveringer</a>
        <div class="meta">
            Deltaker: <strong><?= e($attempt['participant_name']) ?></strong>
            (<?= e($attempt['participant_email']) ?>)<br>
            Startet: <?= e($attempt['started_at']) ?>
            <?php if ($attempt['finished_at']): ?>
                – Levert: <?= e($attempt['finished_at']) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="section">
<h2>Besvarelser</h2>

<?php foreach ($tasks as $task):
    $payload = json_decode($task['payload_json'], true) ?: [];
    $taskId  = (int)$task['id'];
    $answer  = $answers[$taskId] ?? null;
?>
<div class="task">
    <div class="prompt">
        <?= e($payload['prompt'] ?? $payload['sentence'] ?? 'Oppgave') ?>
    </div>

    <div class="answer">
        <?php
        if ($answer === null || $answer === '') {
            echo '<em>Ingen besvarelse</em>';
        }
        elseif (($task['task_type'] ?? '') === 'mcq' && is_numeric($answer) && isset($payload['choices'])) {
            echo e($payload['choices'][(int)$answer] ?? '');
        }
        elseif (is_array($answer)) {
            echo e(json_encode($answer, JSON_UNESCAPED_UNICODE));
        }
        else {
            echo nl2br(e((string)$answer));
        }
        ?>
    </div>
</div>
<?php endforeach; ?>

</div>

<div class="section">
<h2>Lærerkommentar</h2>

<?php if ($msg): ?>
<p style="color:#065f46;font-weight:700"><?= e($msg) ?></p>
<?php endif; ?>

<form method="post" onsubmit="
document.getElementById('teacher_comment_html').value =
document.getElementById('editor').innerHTML;
">

<div class="hn-editor-toolbar">
    <button type="button" onclick="document.execCommand('bold')">B</button>
    <button type="button" onclick="document.execCommand('italic')">I</button>
    <button type="button" onclick="document.execCommand('insertUnorderedList')">•</button>
</div>

<div id="editor" class="hn-editor" contenteditable="true">
<?= $attempt['teacher_comment'] ?? '' ?>
</div>

<input type="hidden" name="teacher_comment_html" id="teacher_comment_html">

<div style="margin-top:16px">
    <button type="submit">Lagre kommentar</button>
</div>
</form>

<?php if ($attempt['reviewed_at']): ?>
<p class="meta" style="margin-top:14px">
Kommentert: <?= e($attempt['reviewed_at']) ?>
</p>
<?php endif; ?>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
