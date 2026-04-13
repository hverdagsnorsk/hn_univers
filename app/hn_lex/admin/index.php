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

/* ==========================================================
   HENT STATUS
========================================================== */

$counts = [
    'pending' => 0,
    'done' => 0,
    'failed' => 0,
    'total' => 0
];

$stmt = $pdo->query("
    SELECT status, COUNT(*) as cnt
    FROM lex_generation_queue
    GROUP BY status
");

foreach ($stmt as $row) {
    $counts[$row['status']] = (int)$row['cnt'];
    $counts['total'] += (int)$row['cnt'];
}

/* ==========================================================
   SISTE AKTIVITET
========================================================== */

$latest = $pdo->query("
    SELECT word, status, created_at
    FROM lex_generation_queue
    ORDER BY created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>HN Lex Admin</title>

<style>
body {
    font-family: Arial;
    background:#f5f5f5;
    padding:20px;
}

h1 {
    margin-bottom:20px;
}

.grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(200px,1fr));
    gap:15px;
    margin-bottom:30px;
}

.card {
    background:white;
    padding:20px;
    border-radius:8px;
    text-align:center;
}

.card .label {
    font-size:14px;
    color:#666;
}

.card .value {
    font-size:28px;
    font-weight:bold;
    margin-top:5px;
}

.menu {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
    gap:15px;
    margin-bottom:30px;
}

.menu a {
    display:block;
    background:#3C6464;
    color:white;
    text-decoration:none;
    padding:15px;
    border-radius:8px;
    text-align:center;
    font-weight:bold;
}

.menu a.secondary {
    background:#2F8485;
}

.menu a.warn {
    background:#c62828;
}

.table {
    background:white;
    border-radius:8px;
    overflow:hidden;
}

table {
    width:100%;
    border-collapse:collapse;
}

th, td {
    padding:10px;
    border-bottom:1px solid #eee;
    text-align:left;
}

th {
    background:#fafafa;
}

.badge {
    padding:4px 8px;
    border-radius:12px;
    font-size:12px;
    font-weight:bold;
}

.pending { background:#fff3cd; color:#8a6d3b; }
.done { background:#d4edda; color:#155724; }
.failed { background:#f8d7da; color:#721c24; }
</style>

</head>
<body>

<h1>HN Lex – Admin</h1>

<!-- STATUS -->
<div class="grid">

    <div class="card">
        <div class="label">Totalt</div>
        <div class="value"><?= $counts['total'] ?></div>
    </div>

    <div class="card">
        <div class="label">Pending</div>
        <div class="value"><?= $counts['pending'] ?? 0 ?></div>
    </div>

    <div class="card">
        <div class="label">Ferdig</div>
        <div class="value"><?= $counts['done'] ?? 0 ?></div>
    </div>

    <div class="card">
        <div class="label">Feilet</div>
        <div class="value"><?= $counts['failed'] ?? 0 ?></div>
    </div>

</div>

<!-- MENY -->
<div class="menu">

    <a href="/hn_lex/admin/generation_review.php">
        🔎 Gå til gjennomgang (review)
    </a>

    <a href="/hn_lex/admin/generation_review.php?filter=pending" class="secondary">
        ⏳ Kun pending
    </a>

    <a href="/hn_lex/admin/generation_review.php?filter=done" class="secondary">
        ✅ Ferdige
    </a>

    <a href="/hn_lex/admin/generation_review.php?filter=failed" class="warn">
        ⚠️ Feilede oppslag
    </a>

</div>

<!-- SISTE AKTIVITET -->
<h2>Siste oppslag</h2>

<div class="table">
<table>
<thead>
<tr>
    <th>Ord</th>
    <th>Status</th>
    <th>Tid</th>
</tr>
</thead>
<tbody>

<?php foreach ($latest as $row): ?>
<tr>
    <td><?= h($row['word']) ?></td>
    <td>
        <span class="badge <?= h($row['status']) ?>">
            <?= h($row['status']) ?>
        </span>
    </td>
    <td><?= h($row['created_at']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

</body>
</html>