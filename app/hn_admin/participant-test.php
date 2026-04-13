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
  <link rel="stylesheet" href="admin-test.css">

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
