<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| hn_admin/participant.php
| Deltakerprofil – progresjon
|------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('PDO mangler.');
}

$email = trim($_GET['email'] ?? '');
if ($email === '') {
    exit('Mangler e-post.');
}

/* --------------------------------------------------
   Hent alle innleveringer for deltaker
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT
        a.id,
        a.started_at,
        a.finished_at,
        a.teacher_comment,
        t.title,
        (
            SELECT COUNT(*)
            FROM JSON_TABLE(
                a.answers_json,
                '$.*' COLUMNS (val JSON PATH '$')
            ) AS j
        ) AS task_count
    FROM attempts a
    JOIN texts t ON t.id = a.text_id
    WHERE a.participant_email = :email
    ORDER BY a.started_at ASC
");
$stmt->execute(['email' => $email]);
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$attempts) {
    exit('Ingen innleveringer funnet for denne deltakeren.');
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Deltakerprofil – <?= e($email) ?></title>
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

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
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

.status-new{
    color:#b00020;
    font-weight:700;
}

.status-reviewed{
    color:#0a7a2f;
    font-weight:700;
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
        <h1>Deltakerprofil</h1>
        <a href="attempts.php">← Tilbake til innleveringer</a>
        <div class="meta">
            <strong>E-post:</strong> <?= e($email) ?><br>
            <strong>Antall innleveringer:</strong> <?= count($attempts) ?>
        </div>
    </div>
</div>

<div class="section">
<h2>Progresjon</h2>

<table>
<thead>
<tr>
    <th>Dato</th>
    <th>Tekst</th>
    <th>Oppgaver</th>
    <th>Status</th>
    <th></th>
</tr>
</thead>
<tbody>

<?php foreach ($attempts as $a): ?>
<tr>
    <td>
        <?php
            $dt = $a['finished_at'] ?: $a['started_at'];
            echo e(date('Y-m-d H:i', strtotime($dt)));
        ?>
    </td>

    <td><strong><?= e($a['title']) ?></strong></td>

    <td><?= (int)$a['task_count'] ?></td>

    <td class="<?= $a['teacher_comment'] ? 'status-reviewed' : 'status-new' ?>">
        <?= $a['teacher_comment'] ? 'Kommentert' : 'Ny' ?>
    </td>

    <td>
        <a href="attempt_view.php?id=<?= (int)$a['id'] ?>">Se besvarelse</a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
