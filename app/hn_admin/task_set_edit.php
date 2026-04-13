<?php
declare(strict_types=1);

/*
|-------------------------------------------------------------------------- 
| hn_admin/task_set_edit.php
| Admin – Rediger oppgavesett + velg oppgaver + rekkefølge
| (Løsning B + auto-rekkefølge + statusfilter + layout-kontrakt)
|-------------------------------------------------------------------------- 
*/

require_once __DIR__ . '/bootstrap.php';

require_once __DIR__ . '/../hn_books/engine/task_set_utils.php';

$error = '';
$success = '';

$setId = (int)($_GET['id'] ?? 0);
if ($setId <= 0) {
    http_response_code(400);
    exit('Ugyldig sett-ID');
}

/* --------------------------------------------------
   Hent sett + tekst
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT s.*, x.book_key, x.text_key, x.title AS text_title
    FROM task_sets s
    JOIN texts x ON x.id = s.text_id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $setId]);
$set = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$set) exit('Oppgavesett ikke funnet');

$textId = (int)$set['text_id'];

/* --------------------------------------------------
   POST: lagre
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title  = trim($_POST['title'] ?? '');
    $active = isset($_POST['active']) ? 1 : 0;

    if ($title === '') {
        $error = 'Tittel mangler.';
    } else {

        db()->prepare("
            UPDATE task_sets
            SET title = :t, active = :a
            WHERE id = :id
        ")->execute([
            't' => $title,
            'a' => $active,
            'id'=> $setId
        ]);

        db()->beginTransaction();
        db()->prepare("DELETE FROM task_set_items WHERE task_set_id = :id")
            ->execute(['id'=>$setId]);

        $include = $_POST['include'] ?? [];
        $order   = $_POST['order'] ?? [];

        $ins = db()->prepare("
            INSERT INTO task_set_items (task_set_id, task_id, sort_order)
            VALUES (:sid,:tid,:ord)
        ");

        foreach ($include as $tid => $_) {
            $ins->execute([
                'sid'=>$setId,
                'tid'=>(int)$tid,
                'ord'=>(int)($order[$tid] ?? 0)
            ]);
        }

        db()->commit();
        header("Location: task_set_edit.php?id=$setId&ok=1");
        exit;
    }
}

if (isset($_GET['ok'])) $success = 'Oppgavesett oppdatert.';

/* --------------------------------------------------
   Oppgaver
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT id, task_type, status, payload_json
    FROM tasks
    WHERE text_id = :tid
    ORDER BY created_at DESC
");
$stmt->execute(['tid'=>$textId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Eksisterende rekkefølge
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT task_id, sort_order
    FROM task_set_items
    WHERE task_set_id = :id
");
$stmt->execute(['id'=>$setId]);

$included = [];
$maxOrder = 0;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $included[(int)$r['task_id']] = (int)$r['sort_order'];
    $maxOrder = max($maxOrder, (int)$r['sort_order']);
}

/* --------------------------------------------------
   Publisering
-------------------------------------------------- */
$pub  = tsu_task_set_publish_status($pdo,$setId);
$link = tsu_participant_link($setId);

function preview_prompt(string $type,array $p): string {
    return match($type) {
        'fill'    => $p['sentence'] ?? '—',
        'match'   => 'Koble '.count($p['pairs'] ?? []).' par',
        default   => $p['prompt'] ?? '—'
    };
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Rediger oppgavesett</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
    color:#0f172a;
}

.container{max-width:1200px;margin:auto}

.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:36px;
}

.header img{max-height:56px}

h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

.card{
    background:#ffffff;
    border-radius:16px;
    padding:26px 28px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    margin-bottom:26px;
}

.notice{
    background:#f1f5f9;
    border-left:6px solid #2f8485;
    padding:16px 18px;
    border-radius:14px;
    margin-bottom:22px;
}

.badge{
    padding:4px 10px;
    border-radius:999px;
    font-size:.75rem;
    font-weight:800;
}

.ok{background:#d1fae5;color:#065f46}
.no{background:#e5e7eb;color:#374151}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:12px;
}

th,td{
    padding:12px 14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
}

th{
    background:#2f8485;
    color:#ffffff;
    font-weight:800;
    font-size:.9rem;
}

tr:hover{background:#f1f9f9}

input[type="text"], input[type="number"]{
    padding:10px;
    border-radius:10px;
    border:1px solid #cbd5e1;
}

.filterbar{margin:14px 0}

button{
    margin-top:20px;
    padding:12px 20px;
    border-radius:999px;
    border:none;
    background:#2f8485;
    color:#ffffff;
    font-weight:800;
    cursor:pointer;
}

button:hover{background:#226c6d}

.footer{
    text-align:center;
    margin-top:60px;
    font-size:.85rem;
    color:#64748b;
}
</style>
</head>

<body>
<div class="container">

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Rediger oppgavesett</h1>
        <div class="sub">
            Tekst: <strong><?= e($set['book_key']) ?> / <?= e($set['text_key']) ?></strong>
            · <?= e($set['text_title']) ?>
        </div>
        <a href="task_sets.php">← Tilbake til oppgavesett</a>
    </div>
</div>

<div class="notice">
    <span class="badge <?= $pub['published']?'ok':'no' ?>">
        <?= $pub['published']?'Publisert':'Ikke publisert' ?>
    </span>
    <div style="margin-top:8px">
        <strong>Deltakerlenke:</strong><br>
        <code><?= e($link) ?></code>
    </div>
</div>

<div class="card">

<?php if($error): ?><div class="notice"><?= e($error) ?></div><?php endif; ?>
<?php if($success): ?><div class="notice"><?= e($success) ?></div><?php endif; ?>

<form method="post">

<label>Tittel</label>
<input type="text" name="title" value="<?= e($set['title']) ?>">

<label style="display:block;margin-top:10px">
<input type="checkbox" name="active" <?= $set['active']?'checked':'' ?>>
 Aktivt sett
</label>

<div class="filterbar">
<label>
<input type="checkbox" id="onlyApproved" checked>
 Vis bare approved
</label>
</div>

<table>
<thead>
<tr>
<th>Med</th>
<th>Rekkefølge</th>
<th>Oppgave</th>
<th>Type</th>
<th>Status</th>
<th>ID</th>
</tr>
</thead>
<tbody>

<?php foreach ($tasks as $t):
    $tid=(int)$t['id'];
    $p=json_decode($t['payload_json'],true)?:[];
    $isOn=isset($included[$tid]);
    $ord=$isOn?$included[$tid]:++$maxOrder;
?>
<tr data-status="<?= e($t['status']) ?>">
<td>
<input type="checkbox"
       name="include[<?= $tid ?>]"
       <?= $isOn?'checked':'' ?>
       onchange="if(this.checked&&!this.dataset.set){this.dataset.set=1;this.closest('tr').querySelector('.ord').value=<?= $maxOrder ?>;}">
</td>
<td>
<input class="ord" type="number" name="order[<?= $tid ?>]" value="<?= $isOn?$ord:'' ?>" style="width:80px">
</td>
<td><?= e(preview_prompt($t['task_type'],$p)) ?></td>
<td><?= e($t['task_type']) ?></td>
<td><?= e($t['status']) ?></td>
<td><?= $tid ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<button type="submit">Lagre oppgavesett</button>
</form>

</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>

<script>
document.getElementById('onlyApproved').addEventListener('change',e=>{
  document.querySelectorAll('tbody tr').forEach(tr=>{
    tr.style.display = (!e.target.checked || tr.dataset.status==='approved') ? '' : 'none';
  });
});
</script>

</body>
</html>
