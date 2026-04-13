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
   Hent forsøk + resultater
-------------------------------------------------- */
$stmt = $pdo->query("
    SELECT 
        a.id AS attempt_id,
        a.participant_name,
        a.participant_email,
        a.started_at,
        a.finished_at,
        COUNT(r.id) AS total_tasks,
        SUM(r.is_correct) AS correct_tasks
    FROM attempts a
    LEFT JOIN responses r ON r.attempt_id = a.id
    GROUP BY a.id
    ORDER BY a.started_at DESC
");

$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Resultater</title>
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

.header img{
    max-height:56px;
}

h1{
    margin:0;
    color:#2f8485;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
}

th, td{
    padding:12px 14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}

th{
    background:#2f8485;
    color:#ffffff;
    font-weight:700;
    font-size:.95rem;
}

tr:hover{
    background:#f1f9f9;
}

.badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:700;
}

.badge-finished{
    background:#d1fae5;
    color:#065f46;
}

.badge-running{
    background:#fde68a;
    color:#92400e;
}

.score{
    font-weight:700;
}

.score-good{color:#065f46}
.score-mid{color:#92400e}
.score-low{color:#b91c1c}

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:600;
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

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Resultater – oppgaver</h1>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<table>
<thead>
<tr>
    <th>Deltaker</th>
    <th>E-post</th>
    <th>Status</th>
    <th>Resultat</th>
    <th>Startet</th>
    <th>Fullført</th>
</tr>
</thead>

<tbody>
<?php foreach ($attempts as $a):

    $total   = (int)$a['total_tasks'];
    $correct = (int)$a['correct_tasks'];
    $percent = $total > 0 ? round(($correct / $total) * 100) : 0;

    if ($percent >= 80) {
        $scoreClass = 'score-good';
    } elseif ($percent >= 50) {
        $scoreClass = 'score-mid';
    } else {
        $scoreClass = 'score-low';
    }
?>
<tr>
    <td>
        <strong><?= htmlspecialchars($a['participant_name']) ?></strong>
    </td>

    <td><?= htmlspecialchars($a['participant_email']) ?></td>

    <td>
        <?php if ($a['finished_at']): ?>
            <span class="badge badge-finished">Fullført</span>
        <?php else: ?>
            <span class="badge badge-running">Pågår</span>
        <?php endif; ?>
    </td>

    <td>
        <span class="score <?= $scoreClass ?>">
            <?= $correct ?> / <?= $total ?> (<?= $percent ?>%)
        </span>
    </td>

    <td><?= htmlspecialchars($a['started_at']) ?></td>

    <td><?= $a['finished_at'] ? htmlspecialchars($a['finished_at']) : '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
