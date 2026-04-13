<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------|
| hn_admin/tasks_edit.php
| Rediger oppgave – typebevisst editor (mcq / fill / short / match / writing)
|--------------------------------------------------------------------------|
*/

require_once __DIR__ . '/bootstrap.php';

/* --------------------------------------------------
   Hent oppgave
-------------------------------------------------- */
$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
    http_response_code(400);
    exit('Ugyldig oppgave-ID');
}

$stmt = db()->prepare("
    SELECT t.*, x.title AS text_title
    FROM tasks t
    JOIN texts x ON x.id = t.text_id
    WHERE t.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $taskId]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task) {
    http_response_code(404);
    exit('Oppgave ikke funnet');
}

$type    = (string)$task['task_type'];
$payload = json_decode($task['payload_json'], true) ?? [];

/* --------------------------------------------------
   POST: lagre endringer
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $newPayload = [];

    switch ($type) {

        /* ================= MCQ ================= */
        case 'mcq':
            $choices = array_values(array_filter($_POST['choices'] ?? [], 'strlen'));

            $newPayload = [
                'prompt'  => trim((string)($_POST['prompt'] ?? '')),
                'choices' => $choices,
            ];

            $correct = $_POST['correct'] ?? '';
            if ($correct !== '') {
                $idx = array_search($correct, $choices, true);
                if ($idx !== false) {
                    $newPayload['correct_index'] = $idx;
                }
            }
            break;

        /* ================= FILL ================= */
        case 'fill':
            $newPayload = [
                'sentence' => trim((string)($_POST['sentence'] ?? '')),
                'answer'   => trim((string)($_POST['answer'] ?? '')),
            ];
            break;

        /* ================= SHORT ================= */
        case 'short':
            $newPayload = [
                'prompt' => trim((string)($_POST['prompt'] ?? '')),
            ];
            break;

        /* ================= WRITING ================= */
        case 'writing':
            $newPayload = [
                'prompt' => trim((string)($_POST['prompt'] ?? '')),
            ];
            break;

        /* ================= MATCH ================= */
        case 'match':
            $lefts  = $_POST['left']  ?? [];
            $rights = $_POST['right'] ?? [];

            $pairs = [];
            foreach ($lefts as $i => $l) {
                $l = trim((string)$l);
                $r = trim((string)($rights[$i] ?? ''));
                if ($l !== '' && $r !== '') {
                    $pairs[] = ['left' => $l, 'right' => $r];
                }
            }

            $newPayload = [
                'prompt' => trim((string)($_POST['prompt'] ?? '')),
                'pairs'  => $pairs,
            ];
            break;

        default:
            http_response_code(400);
            exit('Denne oppgavetypen kan ikke redigeres.');
    }

    // Fail fast: ugyldig JSON
    json_encode($newPayload, JSON_THROW_ON_ERROR);

    $stmt = db()->prepare("
        UPDATE tasks
        SET payload_json = :json
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'json' => json_encode($newPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        'id'   => $taskId
    ]);

    header('Location: tasks.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Rediger oppgave</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{font-family:Segoe UI,system-ui,sans-serif;background:#f4f6f6;padding:30px}
.header{display:flex;align-items:center;gap:20px;margin-bottom:30px}
.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

.card{
    background:#fff;border-radius:16px;padding:28px 32px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);max-width:900px
}

label{font-weight:700;display:block;margin-top:18px}
textarea,input,select{
    width:100%;padding:12px;margin-top:6px;
    border-radius:10px;border:1px solid #cbd5e1;font-size:1rem
}
textarea{resize:vertical}

.grid-2{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.actions{margin-top:32px;display:flex;gap:12px}
button{
    padding:12px 20px;border-radius:999px;border:none;
    background:#2f8485;color:#fff;font-weight:800;cursor:pointer
}
button:hover{background:#226c6d}

a.secondary{
    padding:12px 20px;border-radius:999px;
    background:#64748b;color:#fff;text-decoration:none;font-weight:800
}
a.secondary:hover{background:#475569}

.footer{text-align:center;margin-top:60px;font-size:.85rem;color:#64748b}
</style>
</head>

<body>

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Rediger oppgave</h1>
        <div class="sub">
            Tekst: <strong><?= e($task['text_title']) ?></strong> ·
            Type: <strong><?= e($type) ?></strong> ·
            Status: <strong><?= e($task['status']) ?></strong>
        </div>
        <a href="tasks.php">← Tilbake til oppgaver</a>
    </div>
</div>

<div class="card">
<form method="post">

<?php if ($type === 'mcq'): ?>

    <label>Spørsmål</label>
    <textarea name="prompt"><?= e($payload['prompt'] ?? '') ?></textarea>

    <label>Svaralternativer</label>
    <div class="grid-2">
        <?php for ($i=0;$i<4;$i++): ?>
            <input name="choices[]" value="<?= e($payload['choices'][$i] ?? '') ?>">
        <?php endfor; ?>
    </div>

    <label>Korrekt svar</label>
    <select name="correct">
        <option value="">— velg —</option>
        <?php foreach (($payload['choices'] ?? []) as $i=>$opt): ?>
            <option value="<?= e($opt) ?>" <?= (($payload['correct_index'] ?? -1)===$i)?'selected':'' ?>>
                <?= e($opt) ?>
            </option>
        <?php endforeach; ?>
    </select>

<?php elseif ($type === 'fill'): ?>

    <label>Setning (bruk __ for hull)</label>
    <textarea name="sentence"><?= e($payload['sentence'] ?? '') ?></textarea>

    <label>Riktig svar</label>
    <input name="answer" value="<?= e($payload['answer'] ?? '') ?>">

<?php elseif ($type === 'short' || $type === 'writing'): ?>

    <label><?= $type === 'writing' ? 'Skriveoppgave' : 'Spørsmål' ?></label>
    <textarea name="prompt"><?= e($payload['prompt'] ?? '') ?></textarea>

<?php elseif ($type === 'match'): ?>

    <label>Instruksjon</label>
    <textarea name="prompt"><?= e($payload['prompt'] ?? '') ?></textarea>

    <label>Koble sammen (venstre → høyre)</label>
    <div class="grid-2">
        <?php
        $pairs = $payload['pairs'] ?? [];
        for ($i=0; $i<5; $i++):
        ?>
            <input name="left[]"  placeholder="Venstre"
                   value="<?= e($pairs[$i]['left']  ?? '') ?>">
            <input name="right[]" placeholder="Høyre"
                   value="<?= e($pairs[$i]['right'] ?? '') ?>">
        <?php endfor; ?>
    </div>

<?php else: ?>

    <p>Denne oppgavetypen kan ikke redigeres i UI.</p>

<?php endif; ?>

<div class="actions">
    <button>Lagre endringer</button>
    <a href="tasks.php" class="secondary">Avbryt</a>
</div>

</form>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
