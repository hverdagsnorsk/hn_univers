<?php
declare(strict_types=1);

require_once __DIR__ . '/../_config/config.php';

/* --------------------------------------------------
   Session + tilgang
-------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Ingen tilgang');
}

/* --------------------------------------------------
   Oppdater status (PRG)
-------------------------------------------------- */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['task_id'], $_POST['new_status'])
) {
    $stmt = $pdo->prepare("
        UPDATE tasks
        SET status = :status
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        'status' => $_POST['new_status'],
        'id'     => (int)$_POST['task_id']
    ]);

    header('Location: tasks.php?' . http_build_query($_GET));
    exit;
}

/* --------------------------------------------------
   Tekster (filter)
-------------------------------------------------- */
$texts = $pdo->query("
    SELECT id, title
    FROM texts
    ORDER BY title
")->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Filter
-------------------------------------------------- */
$where  = '';
$params = [];

if (!empty($_GET['text_id'])) {
    $where = 'WHERE t.text_id = :text_id';
    $params['text_id'] = (int)$_GET['text_id'];
}

/* --------------------------------------------------
   Oppgaver
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.task_type,
        t.status,
        t.created_at,
        x.title AS text_title,
        t.payload_json
    FROM tasks t
    JOIN texts x ON x.id = t.text_id
    $where
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Oppgaver</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
}

/* HEADER (samme stil som attempts.php) */
.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:30px;
}

.header img{
    max-height:56px;
}

h1{
    margin:0;
    color:#2f8485;
}

.sub{
    color:#64748b;
    font-size:.95rem;
}

/* FILTER */
.filter{
    margin-bottom:24px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
}

th, td{
    padding:14px 16px;
    border-bottom:1px solid #e5e7eb;
    vertical-align:top;
}

th{
    background:#2f8485;
    color:#ffffff;
    font-weight:700;
    font-size:.9rem;
}

tr:hover{
    background:#f1f9f9;
}

/* BADGES */
.badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:.75rem;
    font-weight:700;
}

.status-approved{background:#d1fae5;color:#065f46}
.status-draft{background:#fde68a;color:#92400e}
.status-archived{background:#e5e7eb;color:#374151}

small{color:#64748b}

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:600;
}

select, button{
    padding:6px 8px;
    font-size:.9rem;
}

.footer{
    text-align:center;
    margin-top:50px;
    font-size:.85rem;
    color:#64748b;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Oppgaver</h1>
        <div class="sub">AI-genererte og redigerte oppgaver • 2026</div>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<!-- FILTER -->
<form method="get" class="filter">
    <label>
        Filtrer på tekst:
        <select name="text_id" onchange="this.form.submit()">
            <option value="">Alle tekster</option>
            <?php foreach ($texts as $t): ?>
                <option value="<?= $t['id'] ?>"
                    <?= (($_GET['text_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($t['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
</form>

<!-- TABLE -->
<?php if ($tasks): ?>
<table>
<thead>
<tr>
    <th>Tekst</th>
    <th>Type</th>
    <th>Oppgave</th>
    <th>Status</th>
    <th>Endre</th>
    <th>Opprettet</th>
</tr>
</thead>
<tbody>
<?php foreach ($tasks as $task):
    $payload = json_decode($task['payload_json'], true);
    $prompt  = $payload['prompt'] ?? '—';
?>
<tr>
    <td><strong><?= htmlspecialchars($task['text_title']) ?></strong></td>

    <td><?= htmlspecialchars($task['task_type']) ?></td>

    <td>
        <a href="tasks_edit.php?id=<?= $task['id'] ?>">
            <?= htmlspecialchars($prompt) ?>
        </a><br>
        <small>ID: <?= $task['id'] ?></small>
    </td>

    <td>
        <span class="badge status-<?= $task['status'] ?>">
            <?= $task['status'] ?>
        </span>
    </td>

    <td>
        <form method="post">
            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
            <select name="new_status">
                <option value="draft" <?= $task['status']==='draft'?'selected':'' ?>>Utkast</option>
                <option value="approved" <?= $task['status']==='approved'?'selected':'' ?>>Godkjent</option>
                <option value="archived" <?= $task['status']==='archived'?'selected':'' ?>>Arkivert</option>
            </select>
            <button>Lagre</button>
        </form>
    </td>

    <td><?= htmlspecialchars($task['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p>Ingen oppgaver funnet.</p>
<?php endif; ?>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
