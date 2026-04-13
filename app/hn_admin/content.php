<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------
 | Admin – Innhold for elevside
 |------------------------------------------------------------
 | Dokumenter, videoer og kursplan
 */

require_once __DIR__ . '/bootstrap.php';

$docs     = read_json(DOC_FILE);
$vids     = read_json(VID_FILE);
$schedule = read_json(SCH_FILE);
$msg = '';

/* ================= DOKUMENTER ================= */

if (($_POST['type'] ?? '') === 'upload_doc' && isset($_FILES['doc']) && $_FILES['doc']['error'] === 0) {
    $name = time() . '_' . basename($_FILES['doc']['name']);
    move_uploaded_file($_FILES['doc']['tmp_name'], BASE . '/docs/' . $name);

    $docs[] = [
        'title' => trim($_POST['title'] ?? $name),
        'file'  => 'docs/' . $name,
        'date'  => date('Y-m-d H:i')
    ];
    save_json(DOC_FILE, $docs);
    $msg = 'Dokument lastet opp';
}

if (($_POST['type'] ?? '') === 'delete_doc' && isset($_POST['index'])) {
    $i = (int)$_POST['index'];
    if (isset($docs[$i])) {
        @unlink(BASE . '/' . $docs[$i]['file']);
        array_splice($docs, $i, 1);
        save_json(DOC_FILE, $docs);
        $msg = 'Dokument slettet';
    }
}

/* ================= VIDEO ================= */

if (($_POST['type'] ?? '') === 'add_video') {
    preg_match('~(?:v=|be/)([^&?/]+)~', $_POST['youtube'], $m);
    if (!empty($m[1])) {
        $vids[] = [
            'title' => trim($_POST['title'] ?? 'Video'),
            'youtube_id' => $m[1],
            'date' => date('Y-m-d H:i')
        ];
        save_json(VID_FILE, $vids);
        $msg = 'Video lagt til';
    }
}

if (($_POST['type'] ?? '') === 'delete_video' && isset($_POST['index'])) {
    $i = (int)$_POST['index'];
    if (isset($vids[$i])) {
        array_splice($vids, $i, 1);
        save_json(VID_FILE, $vids);
        $msg = 'Video slettet';
    }
}

/* ================= KURSPLAN ================= */

if (($_POST['type'] ?? '') === 'save_schedule') {
    $decoded = json_decode($_POST['json'], true);
    if (is_array($decoded)) {
        save_json(SCH_FILE, $decoded);
        $msg = 'Kursplan lagret';
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Innhold</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
}

/* HEADER */
.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:40px;
}
.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

/* CONTAINER */
.container{max-width:1100px;margin:auto}

/* CARD */
.card{
    background:#ffffff;
    border-radius:16px;
    padding:28px 30px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    margin-bottom:32px;
}
.card h2{margin-top:0}

/* FORM */
.form{
    display:grid;
    grid-template-columns:1fr 1fr auto;
    gap:12px;
    margin-bottom:18px;
}
.form input, .form button, textarea{
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #cbd5e1;
    font-size:1rem;
}
.form button{
    background:#2f8485;
    color:#fff;
    border:none;
    font-weight:700;
    cursor:pointer;
}
.form button:hover{background:#226c6d}

/* LIST */
.list{
    list-style:none;
    padding:0;
    margin:0;
}
.list li{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px 0;
    border-bottom:1px solid #e5e7eb;
}
.list li:last-child{border-bottom:none}
.list form button{
    background:#e11d48;
    border:none;
    padding:6px 12px;
    border-radius:999px;
    color:#fff;
    font-weight:600;
    cursor:pointer;
}

/* MESSAGE */
.msg{
    background:#ecfeff;
    border-left:4px solid #2f8485;
    padding:12px 16px;
    margin-bottom:24px;
}

/* FOOTER */
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

<!-- HEADER -->
<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Innhold – Elevside</h1>
        <div class="sub">Dokumenter, videoer og kursplan • 2026</div>
    </div>
</div>

<p><a href="index.php">← Tilbake til dashboard</a></p>

<?php if ($msg): ?>
<div class="msg"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

<!-- DOKUMENTER -->
<div class="card">
<h2>📄 Dokumenter</h2>

<form method="post" enctype="multipart/form-data" class="form">
    <input type="hidden" name="type" value="upload_doc">
    <input type="file" name="doc" required>
    <input name="title" placeholder="Tittel på dokument">
    <button>Last opp</button>
</form>

<?php if ($docs): ?>
<ul class="list">
<?php foreach ($docs as $i => $d): ?>
<li>
    <?= htmlspecialchars($d['title']) ?>
    <form method="post">
        <input type="hidden" name="type" value="delete_doc">
        <input type="hidden" name="index" value="<?= $i ?>">
        <button onclick="return confirm('Slette dokument?')">Slett</button>
    </form>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>Ingen dokumenter lagt ut ennå.</p>
<?php endif; ?>
</div>

<!-- VIDEOER -->
<div class="card">
<h2>🎥 Videoer</h2>

<form method="post" class="form">
    <input type="hidden" name="type" value="add_video">
    <input name="title" placeholder="Videotittel">
    <input name="youtube" placeholder="YouTube-lenke" required>
    <button>Legg til</button>
</form>

<?php if ($vids): ?>
<ul class="list">
<?php foreach ($vids as $i => $v): ?>
<li>
    <?= htmlspecialchars($v['title']) ?>
    <form method="post">
        <input type="hidden" name="type" value="delete_video">
        <input type="hidden" name="index" value="<?= $i ?>">
        <button onclick="return confirm('Slette video?')">Slett</button>
    </form>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>Ingen videoer lagt til ennå.</p>
<?php endif; ?>
</div>

<!-- KURSPLAN -->
<div class="card">
<h2>🗓️ Kursplan (JSON)</h2>

<form method="post">
    <input type="hidden" name="type" value="save_schedule">
    <textarea name="json" rows="10"><?= htmlspecialchars(json_encode($schedule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea>
    <button style="margin-top:12px">Lagre kursplan</button>
</form>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>
</body>
</html>
