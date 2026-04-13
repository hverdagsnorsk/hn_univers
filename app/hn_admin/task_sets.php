<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/task_sets.php
| Admin – Oppgavesett (Løsning B, felles publiseringsregler)
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/../hn_books/engine/task_set_utils.php';

$error = '';
$success = '';

/* --------------------------------------------------
   PRG: Opprett sett
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $textId = (int)($_POST['text_id'] ?? 0);
    $title  = trim((string)($_POST['title'] ?? ''));
    $desc   = trim((string)($_POST['description'] ?? ''));
    $active = isset($_POST['active']) ? 1 : 0;

    if ($textId <= 0) {
        $error = 'Velg tekst.';
    } elseif ($title === '') {
        $error = 'Tittel mangler.';
    } else {
        $stmt = db()->prepare("
            INSERT INTO task_sets (text_id, title, description, active, created_at)
            VALUES (:text_id, :title, :description, :active, NOW())
        ");
        $stmt->execute([
            'text_id'     => $textId,
            'title'       => $title,
            'description' => ($desc === '' ? null : $desc),
            'active'      => $active
        ]);

        header('Location: task_sets.php?ok=created');
        exit;
    }
}

/* --------------------------------------------------
   PRG: Toggle active
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $setId = (int)($_POST['set_id'] ?? 0);
    if ($setId > 0) {
        db()->prepare("UPDATE task_sets SET active = 1 - active WHERE id = :id LIMIT 1")
            ->execute(['id' => $setId]);
    }
    header('Location: task_sets.php');
    exit;
}

/* --------------------------------------------------
   PRG: Slett sett
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $setId = (int)($_POST['set_id'] ?? 0);
    if ($setId > 0) {
        db()->prepare("DELETE FROM task_sets WHERE id = :id LIMIT 1")
            ->execute(['id' => $setId]);
        header('Location: task_sets.php?ok=deleted');
        exit;
    }
}

/* --------------------------------------------------
   Flash
-------------------------------------------------- */
if (!empty($_GET['ok'])) {
    if ($_GET['ok'] === 'created') $success = 'Oppgavesett opprettet.';
    if ($_GET['ok'] === 'deleted') $success = 'Oppgavesett slettet.';
}

/* --------------------------------------------------
   Tekster (for oppretting)
-------------------------------------------------- */
$texts = db()->query("
    SELECT id, book_key, text_key, title
    FROM texts
    WHERE active = 1
    ORDER BY book_key, id
")->fetchAll(PDO::FETCH_ASSOC);

$textsByBook = [];
foreach ($texts as $t) {
    $textsByBook[$t['book_key']][] = $t;
}

/* --------------------------------------------------
   Liste oppgavesett
-------------------------------------------------- */
$sets = db()->query("
    SELECT
        s.id,
        s.text_id,
        s.title,
        s.description,
        s.active,
        s.created_at,
        x.book_key,
        x.text_key,
        x.title AS text_title
    FROM task_sets s
    JOIN texts x ON x.id = s.text_id
    ORDER BY x.book_key, x.id, s.id
")->fetchAll(PDO::FETCH_ASSOC);

function esc_local(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Oppgavesett</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{font-family:Segoe UI,system-ui,sans-serif;background:#f4f6f6;padding:30px;color:#0f172a}
.container{max-width:1200px;margin:auto}

.header{display:flex;align-items:center;gap:20px;margin-bottom:30px}
.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

.card{background:#fff;border-radius:16px;padding:26px 28px;box-shadow:0 8px 22px rgba(0,0,0,.08);margin-bottom:24px}

.info{
    border-left:6px solid #2f8485;
    background:#ffffff;
    padding:16px 18px;
    margin-bottom:26px;
    border-radius:14px;
}

label{font-weight:700}
select,input,textarea,button{
    width:100%;
    margin-top:6px;
    margin-bottom:14px;
    padding:12px;
    font-size:1rem;
    border-radius:10px;
    border:1px solid #cbd5e1;
}
textarea{min-height:90px}

.row{display:grid;grid-template-columns:1fr 1fr;gap:18px}

button.primary{
    background:#2f8485;
    color:#fff;
    font-weight:800;
    border:none;
    cursor:pointer;
}
button.primary:hover{background:#226c6d}

.msg-success{background:#ecfeff;border-left:4px solid #16a34a;padding:12px 16px;margin-bottom:14px;color:#065f46;font-weight:700;border-radius:10px}
.msg-error{background:#fef2f2;border-left:4px solid #dc2626;padding:12px 16px;margin-bottom:14px;color:#7f1d1d;font-weight:700;border-radius:10px}

table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
}
th,td{padding:14px 16px;border-bottom:1px solid #e5e7eb;vertical-align:top}
th{background:#2f8485;color:#fff;font-weight:800;font-size:.9rem}
tr:hover{background:#f1f9f9}

.badge{display:inline-block;padding:4px 10px;border-radius:999px;font-size:.75rem;font-weight:900}
.on{background:#d1fae5;color:#065f46}
.off{background:#e5e7eb;color:#374151}
.warn{background:#fde68a;color:#92400e}

.actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.actions form{margin:0}

button.small{width:auto;padding:8px 10px;border-radius:8px;border:none;font-weight:800;cursor:pointer}
button.toggle{background:#f1f5f9}
button.delete{background:#dc2626;color:#fff}
button.delete:hover{background:#b91c1c}

.linkbox{
    background:#f1f5f9;
    padding:6px 8px;
    border-radius:6px;
    font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size:.8rem;
    word-break:break-all;
}

.smallnote{color:#64748b;font-size:.85rem;margin-top:6px}
.footer{text-align:center;margin-top:40px;font-size:.85rem;color:#64748b}
</style>
</head>

<body>
<div class="container">

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Oppgavesett</h1>
        <div class="sub">Publisering og progresjon per tekst • 2026</div>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<div class="info">
    <strong>Publiseringsregel (Løsning B):</strong><br>
    Et oppgavesett er <em>publisert</em> når:
    <ol style="margin:10px 0 0 18px;color:#334155">
        <li>Settet er aktivt (<code>task_sets.active = 1</code>)</li>
        <li>Settet inneholder minst én <strong>godkjent</strong> oppgave (<code>tasks.status = approved</code>)</li>
        <li>Settet inneholder minst én <strong>godkjent skriveoppgave</strong> (<code>tasks.task_type = writing</code>)</li>
    </ol>
    <div class="smallnote">
        VIKTIG: fila som registrerer forsøk heter <strong>attempts.php</strong> og ligger i <strong>www/hn_admin/</strong>.
    </div>
</div>

<div class="card">
<?php if ($error): ?><div class="msg-error"><?= esc_local($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="msg-success"><?= esc_local($success) ?></div><?php endif; ?>

<h2 style="margin-top:0">Opprett nytt oppgavesett</h2>

<form method="post">
<input type="hidden" name="action" value="create">

<label>Tekst</label>
<select name="text_id" required>
    <option value="">Velg tekst …</option>
    <?php foreach ($textsByBook as $book => $items): ?>
        <optgroup label="<?= esc_local($book) ?>">
            <?php foreach ($items as $t): ?>
                <option value="<?= (int)$t['id'] ?>">
                    <?= esc_local($t['text_key']) ?> – <?= esc_local($t['title']) ?>
                </option>
            <?php endforeach; ?>
        </optgroup>
    <?php endforeach; ?>
</select>

<div class="row">
    <div>
        <label>Tittel</label>
        <input name="title" placeholder="F.eks. Oppgaver – del 1">
    </div>
    <div style="display:flex;align-items:end">
        <label style="display:flex;gap:10px;align-items:center;margin:0">
            <input type="checkbox" name="active" checked style="width:auto;margin:0">
            Aktivt sett
        </label>
    </div>
</div>

<label>Beskrivelse (valgfritt)</label>
<textarea name="description"></textarea>

<button class="primary" type="submit">Opprett sett</button>
</form>
</div>

<?php if ($sets): ?>
<table>
<thead>
<tr>
    <th>Tekst</th>
    <th>Oppgavesett</th>
    <th>Telling</th>
    <th>Status</th>
    <th>Deltakerlenke</th>
    <th>Handlinger</th>
</tr>
</thead>
<tbody>
<?php foreach ($sets as $s):
    $sid = (int)$s['id'];
    $pub = tsu_task_set_publish_status($pdo, $sid);
    $counts = $pub['counts'];
    $published = (bool)$pub['published'];
    $link = tsu_participant_link($sid);
    $missingWriting = ((int)$counts['approved_writing'] <= 0);
?>
<tr>
<td>
    <strong><?= esc_local($s['book_key']) ?> / <?= esc_local($s['text_key']) ?></strong><br>
    <small><?= esc_local($s['text_title']) ?></small>
</td>

<td>
    <a href="task_set_edit.php?id=<?= $sid ?>" style="color:#2f8485;font-weight:900;text-decoration:none">
        <?= esc_local($s['title']) ?>
    </a><br>
    <small><?= esc_local((string)($s['description'] ?? '')) ?></small>
</td>

<td>
    <strong><?= (int)$counts['total'] ?></strong> koblinger<br>
    <small><?= (int)$counts['approved_total'] ?> approved · <?= (int)$counts['approved_writing'] ?> writing</small>
</td>

<td>
    <?php if ($published): ?>
        <span class="badge on">publisert</span>
    <?php else: ?>
        <span class="badge off">ikke publisert</span>
        <?php if ($missingWriting): ?>
            <div style="margin-top:6px"><span class="badge warn">mangler writing</span></div>
        <?php endif; ?>
        <?php if (!empty($pub['reasons'])): ?>
            <div class="smallnote" style="margin-top:6px">
                <?= esc_local($pub['reasons'][0]) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</td>

<td>
    <div class="linkbox"><?= esc_local($link) ?></div>
    <div class="smallnote">Lenken fungerer når settet er publisert.</div>
</td>

<td>
<div class="actions">
    <a href="task_set_edit.php?id=<?= $sid ?>" style="color:#2f8485;font-weight:900;text-decoration:none">Rediger</a>

    <form method="post">
        <input type="hidden" name="action" value="toggle">
        <input type="hidden" name="set_id" value="<?= $sid ?>">
        <button type="submit" class="small toggle">
            <?= ((int)$s['active'] === 1) ? 'Deaktiver' : 'Aktiver' ?>
        </button>
    </form>

    <form method="post" onsubmit="return confirm('Slette oppgavesettet permanent?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="set_id" value="<?= $sid ?>">
        <button type="submit" class="small delete">Slett</button>
    </form>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<div class="card"><p>Ingen oppgavesett ennå.</p></div>
<?php endif; ?>

<div class="footer">© 2026 Hverdagsnorsk</div>

</div>
</body>
</html>
