<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN_ADMIN – Systemverktøy
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/_auth.php';
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Systemverktøy – Admin</title>
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
    margin-bottom:40px;
}

.header img{max-height:56px}
h1{margin:0;color:#2f8485}
.sub{color:#64748b;font-size:.95rem}

.container{max-width:1100px;margin:auto}

.card{
    background:#ffffff;
    border-radius:16px;
    padding:26px 28px;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    margin-bottom:28px;
}

.card.warning{
    border-left:6px solid #f59e0b;
}

.card h3{margin-top:0}
.card p{color:#64748b}

button{
    margin-top:14px;
    padding:12px 20px;
    border-radius:999px;
    border:none;
    background:#2f8485;
    color:#fff;
    font-weight:700;
    cursor:pointer;
}

button.warning{background:#f59e0b}
button:hover{opacity:.9}

pre{
    background:#0f172a;
    color:#e5e7eb;
    padding:18px;
    border-radius:12px;
    overflow:auto;
    font-size:.9rem;
}

a{color:#2f8485;font-weight:600;text-decoration:none}

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
    <img src="images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Systemverktøy</h1>
        <div class="sub">
            Avansert vedlikehold • bruk med forsiktighet<br>
            <a href="index.php">← Tilbake til adminpanel</a>
        </div>
    </div>
</div>

<!-- DEBUG (DRY-RUN) -->
<div class="card">
    <h3>🔍 Debug bokstruktur (dry-run)</h3>
    <p>
        Leser bokstrukturen i <code>hn_books/books/</code> uten å endre databasen.
        Bruk denne først for å kontrollere at mapper og HTML-filer er riktige.
    </p>
    <form method="post" action="tools/scan_debug.php">
        <button type="submit">Kjør debug</button>
    </form>
</div>

<!-- SCAN (WRITE TO DB) -->
<div class="card warning">
    <h3>📦 Skann bøker (skriver til DB)</h3>
    <p>
        Leser filsystemet (<code>hn_books/books/</code>) og registrerer nye
        tekster i databasen.
        <strong>Dette gjør endringer i databasen.</strong>
    </p>
    <form method="post" action="tools/scan_books.php">
        <button class="warning" type="submit">Kjør skann</button>
    </form>
</div>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>
</body>
</html>
