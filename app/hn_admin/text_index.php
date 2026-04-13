<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/* --------------------------------------------------
   Hent tekster
-------------------------------------------------- */
$rows = db()->query("
    SELECT id, book_key, text_key, title, level, source_path, created_at, active
    FROM texts
    ORDER BY book_key, id
")->fetchAll(PDO::FETCH_ASSOC);

$textsByBook = [];
foreach ($rows as $r) {
    $textsByBook[$r['book_key']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Tekstindeks</title>
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

.card h2{
    margin:0 0 14px;
    font-size:1.1rem;
    color:#334155;
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

th,td{
    padding:12px 14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}

th{
    background:#2f8485;
    color:#ffffff;
    font-weight:800;
    font-size:.9rem;
}

tr:hover{background:#f1f9f9}

.small{
    color:#64748b;
    font-size:.85rem;
}

.badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:.75rem;
    font-weight:800;
}

.ok{background:#d1fae5;color:#065f46}
.no{background:#e5e7eb;color:#374151}

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:700;
}
a:hover{text-decoration:underline}

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

<!-- ================= HEADER ================= -->
<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Tekstindeks</h1>
        <div class="sub">
            Oversikt over tekster gruppert per bok
        </div>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<!-- ================= CONTENT ================= -->
<?php foreach ($textsByBook as $book => $texts): ?>
<div class="card">

    <h2><?= e($book) ?></h2>

    <table>
        <thead>
            <tr>
                <th>Tekstnøkkel</th>
                <th>Tittel</th>
                <th>Nivå</th>
                <th>Opprettet</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($texts as $t): ?>
            <tr>
                <td><?= e($t['text_key']) ?></td>
                <td><?= e($t['title']) ?></td>
                <td><?= e((string)$t['level']) ?></td>
                <td class="small"><?= e($t['created_at']) ?></td>
                <td>
                    <?php if ((int)$t['active'] === 1): ?>
                        <span class="badge ok">Aktiv</span>
                    <?php else: ?>
                        <span class="badge no">Skjult</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>
<?php endforeach; ?>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>
</body>
</html>
