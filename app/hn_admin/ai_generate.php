<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/ai_generate.php
| AI – kontrollert generering av oppgaver
| - Dynamisk bruk av task_types
| - Valgfritt: lagre + legg direkte i oppgavesett
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/ai/payload_validator.php';

$error = '';
$success = '';
$storedCount = 0;
$rejected = [];

/* --------------------------------------------------
   Hent tekster
-------------------------------------------------- */
$rows = db()->query("
    SELECT id, book_key, text_key, title
    FROM texts
    WHERE active = 1
    ORDER BY book_key, id
")->fetchAll(PDO::FETCH_ASSOC);

$textsByBook = [];
foreach ($rows as $r) {
    $textsByBook[$r['book_key']][] = $r;
}

/* --------------------------------------------------
   Hent oppgavetyper (DB = fasit)
-------------------------------------------------- */
$taskTypes = db()->query("
    SELECT type_key, label, auto_correctable
    FROM task_types
    ORDER BY id
")->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Hent oppgavesett (for «legg direkte i sett»)
-------------------------------------------------- */
$taskSets = db()->query("
    SELECT
        s.id,
        s.title,
        x.book_key,
        x.text_key
    FROM task_sets s
    JOIN texts x ON x.id = s.text_id
    ORDER BY x.book_key, x.text_key, s.id
")->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   POST: lagre oppgaver
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generated_json'])) {

    $textId = (int)($_POST['text_id'] ?? 0);
    $raw    = trim((string)($_POST['generated_json'] ?? ''));
    $setId  = (int)($_POST['attach_set_id'] ?? 0);

    $data = json_decode($raw, true);

    if ($textId <= 0) {
        $error = 'Ingen tekst valgt.';
    } elseif ($raw === '') {
        $error = 'Ingen JSON å lagre.';
    } elseif (!is_array($data)) {
        $error = 'Ugyldig JSON.';
    } else {

        if (isset($data['tasks']) && is_array($data['tasks'])) {
            $data = $data['tasks'];
        }

        if (!array_is_list($data)) {
            $error = 'JSON må være en liste med oppgaver.';
        } else {

            db()->beginTransaction();

            $insertTask = db()->prepare("
                INSERT INTO tasks (
                    text_id,
                    task_type,
                    status,
                    payload_json,
                    created_at
                ) VALUES (
                    :text_id,
                    :task_type,
                    'draft',
                    :payload,
                    NOW()
                )
            ");

            $insertSetItem = db()->prepare("
                INSERT INTO task_set_items (task_set_id, task_id, sort_order)
                VALUES (:sid, :tid, :ord)
            ");

            $sortBase = time();

            foreach ($data as $index => $task) {

                $validationError = null;

                if (!validate_task($task, $validationError)) {
                    $rejected[] = [
                        'index' => $index,
                        'error' => $validationError,
                        'task'  => $task
                    ];
                    continue;
                }

                $insertTask->execute([
                    'text_id'   => $textId,
                    'task_type' => $task['task_type'],
                    'payload'   => json_encode(
                        $task['payload'],
                        JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
                    )
                ]);

                $taskId = (int)db()->lastInsertId();
                $storedCount++;

                if ($setId > 0) {
                    $insertSetItem->execute([
                        'sid' => $setId,
                        'tid' => $taskId,
                        'ord' => $sortBase + $index
                    ]);
                }
            }

            db()->commit();

            $success = $storedCount
                ? "$storedCount oppgaver lagret som utkast."
                : 'Ingen gyldige oppgaver ble lagret.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – AI-generering</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{font-family:Segoe UI,system-ui,sans-serif;background:#f4f6f6;padding:30px}
.container{max-width:1100px;margin:auto}
.header{display:flex;gap:20px;margin-bottom:30px}
.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b}

.card{background:#fff;border-radius:16px;padding:28px 30px;
box-shadow:0 8px 22px rgba(0,0,0,.08);margin-bottom:26px}

label{font-weight:700}
select,textarea,button{
    width:100%;margin-top:6px;margin-bottom:16px;
    padding:12px;border-radius:10px;border:1px solid #cbd5e1
}

textarea{min-height:320px;font-family:Consolas,monospace}

button.primary{background:#2f8485;color:#fff;font-weight:800;border:none;cursor:pointer}
button.secondary{background:#f1f5f9;font-weight:800;cursor:pointer}

.msg-success{background:#ecfeff;border-left:4px solid #16a34a;padding:12px 16px}
.msg-error{background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px}

.debug{background:#0f172a;color:#e5e7eb;padding:14px;border-radius:10px;overflow:auto}
</style>
</head>

<body>
<div class="container">

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>AI – Generer oppgaver</h1>
        <div class="sub">Task types fra database · direkte i oppgavesett</div>
        <a href="index.php">← Tilbake</a>
    </div>
</div>

<div class="card">

<?php if ($error): ?><div class="msg-error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="msg-success"><?= e($success) ?></div><?php endif; ?>

<form method="post">

<label>1. Velg tekst</label>
<select id="text_id" name="text_id" required>
<option value="">Velg tekst …</option>
<?php foreach ($textsByBook as $book => $texts): ?>
<optgroup label="<?= e((string)$book) ?>">
<?php foreach ($texts as $t): ?>
<option value="<?= (int)$t['id'] ?>">
<?= e((string)$t['text_key']) ?> – <?= e((string)$t['title']) ?>
</option>
<?php endforeach; ?>
</optgroup>
<?php endforeach; ?>
</select>

<label>2. Velg oppgavetype</label>
<select id="task_mode">
<?php foreach ($taskTypes as $tt): ?>
<option value="<?= e((string)$tt['type_key']) ?>">
<?= e((string)$tt['label']) ?>
</option>
<?php endforeach; ?>
</select>

<label>3. Legg direkte i oppgavesett (valgfritt)</label>
<select name="attach_set_id">
<option value="0">— Ikke legg i oppgavesett —</option>
<?php foreach ($taskSets as $s): ?>
<option value="<?= (int)$s['id'] ?>">
<?= e((string)$s['book_key']) ?>/<?= e((string)$s['text_key']) ?> – <?= e((string)$s['title']) ?>
</option>
<?php endforeach; ?>
</select>

<button type="button" class="secondary" id="btn-generate">
Generer oppgaver med AI
</button>

<label>4. Generert JSON</label>
<textarea id="generated_json" name="generated_json"></textarea>

<button type="submit" class="primary">
Lagre oppgaver
</button>

</form>

<?php if ($rejected): ?>
<div class="debug">
<pre><?= e(json_encode($rejected, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
</div>
<?php endif; ?>

</div>
</div>

<script>
const btn  = document.getElementById('btn-generate');
const text = document.getElementById('text_id');
const mode = document.getElementById('task_mode');
const area = document.getElementById('generated_json');

btn.onclick = async () => {
    if (!text.value) { alert('Velg tekst først'); return; }

    btn.disabled = true;
    btn.textContent = 'Genererer …';

    try {
        const res = await fetch('ai_generate_run.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body:
                'text_id=' + encodeURIComponent(text.value) +
                '&mode=' + encodeURIComponent(mode.value)
        });
        area.value = await res.text();
    } catch (e) {
        alert('Feil ved AI-generering');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Generer oppgaver med AI';
    }
};
</script>

</body>
</html>
