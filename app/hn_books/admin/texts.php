<?php
declare(strict_types=1);

require_once __DIR__ . '/../_config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Ingen tilgang');
}

/* ======================================================
   KONFIG
====================================================== */
$BOOK_KEY = 'renhold';
$TEXT_DIR = realpath(__DIR__ . '/../Tekster');

/* ======================================================
   OPPDATER TITTEL
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_title') {

    $id    = (int)($_POST['text_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');

    if ($id > 0 && $title !== '') {
        $stmt = $pdo->prepare("
            UPDATE texts
            SET title = :title
            WHERE id = :id
        ");
        $stmt->execute([
            'title' => $title,
            'id'    => $id
        ]);
    }

    header('Location: texts.php');
    exit;
}

/* ======================================================
   REGISTRER NY TEKST
====================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_text') {

    $filename = basename($_POST['filename'] ?? '');

    if ($filename && file_exists($TEXT_DIR . '/' . $filename)) {

        $textKey = strtolower(pathinfo($filename, PATHINFO_FILENAME));

        $check = $pdo->prepare("
            SELECT id FROM texts
            WHERE book_key = :book AND text_key = :text
            LIMIT 1
        ");
        $check->execute([
            'book' => $BOOK_KEY,
            'text' => $textKey
        ]);

        if (!$check->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO texts (
                    book_key,
                    text_key,
                    title,
                    source_path,
                    active,
                    created_at
                ) VALUES (
                    :book_key,
                    :text_key,
                    :title,
                    :source_path,
                    1,
                    NOW()
                )
            ");

            $stmt->execute([
                'book_key'    => $BOOK_KEY,
                'text_key'    => $textKey,
                'title'       => 'Uten tittel (' . $filename . ')',
                'source_path' => '/Renhold/Tekster/' . $filename
            ]);
        }

        header('Location: texts.php');
        exit;
    }
}

/* ======================================================
   HENT TEKSTER
====================================================== */
$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.text_key,
        t.title,
        t.source_path,
        t.active,
        t.created_at,
        COUNT(k.id) AS task_count
    FROM texts t
    LEFT JOIN tasks k ON k.text_id = t.id
    WHERE t.book_key = :book
    GROUP BY t.id
    ORDER BY t.created_at DESC
");
$stmt->execute(['book' => $BOOK_KEY]);
$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   SCAN FILSYSTEM
====================================================== */
$files = glob($TEXT_DIR . '/*.html');
$registeredFiles = array_map(fn($t) => basename($t['source_path']), $texts);

$unregistered = [];
foreach ($files as $file) {
    $base = basename($file);
    if (!in_array($base, $registeredFiles, true)) {
        $unregistered[] = $base;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Tekster – administrasjon</title>
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

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:600;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    margin-bottom:40px;
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

.badge-active{
    background:#d1fae5;
    color:#065f46;
}

.badge-inactive{
    background:#e5e7eb;
    color:#374151;
}

input[type="text"]{
    width:100%;
    padding:6px 8px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    font-weight:600;
}

button{
    padding:6px 14px;
    border-radius:999px;
    border:none;
    background:#2f8485;
    color:#ffffff;
    font-weight:700;
    cursor:pointer;
}

button.warning{
    background:#f59e0b;
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
        <h1>Tekster – administrasjon</h1>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<?php if ($unregistered): ?>
<table>
<thead>
<tr>
    <th>Uregistrerte tekster</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php foreach ($unregistered as $file): ?>
<tr>
    <td><code><?= htmlspecialchars($file) ?></code></td>
    <td style="text-align:right">
        <form method="post">
            <input type="hidden" name="action" value="register_text">
            <input type="hidden" name="filename" value="<?= htmlspecialchars($file) ?>">
            <button class="warning">Registrer</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<table>
<thead>
<tr>
    <th>Tittel</th>
    <th>Tekst-ID</th>
    <th>Oppgaver</th>
    <th>Status</th>
    <th>Opprettet</th>
</tr>
</thead>
<tbody>

<?php foreach ($texts as $t): ?>
<tr>
    <td>
        <form method="post">
            <input type="hidden" name="action" value="update_title">
            <input type="hidden" name="text_id" value="<?= $t['id'] ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($t['title']) ?>">
        </form>
        <small><?= htmlspecialchars($t['source_path']) ?></small><br>
        <a href="../Oppgaver/play.php?book=renhold&text=<?= urlencode($t['text_key']) ?>" target="_blank">
            ▶ Åpne oppgaver
        </a>
    </td>

    <td><?= htmlspecialchars($t['text_key']) ?></td>
    <td><?= (int)$t['task_count'] ?></td>
    <td>
        <?= $t['active']
            ? '<span class="badge badge-active">Aktiv</span>'
            : '<span class="badge badge-inactive">Inaktiv</span>' ?>
    </td>
    <td><?= htmlspecialchars($t['created_at']) ?></td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
