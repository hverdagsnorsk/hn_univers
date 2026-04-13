<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/bootstrap.php';

/* --------------------------------------------------
   Hent dokument-id
-------------------------------------------------- */
$id = $_GET['id'] ?? '';

$html = '';

if (!preg_match('/^[a-z0-9_-]+$/i', $id)) {
    $html = '<p>Ugyldig dokument.</p>';
} else {
    $file = __DIR__ . '/data/original/' . $id . '.html';

    if (!is_file($file)) {
        $html = '<p>Dokument ikke funnet.</p>';
    } else {
        $html = file_get_contents($file);
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <title>HN Lex</title>

  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="style/lex.css">
</head>
<body>

<div class="lex-layout">

  <!-- ===============================
       TEKST
  =============================== -->
  <main class="lex-text">
    <?= $html ?>
  </main>

  <!-- ===============================
       SIDEPANEL
  =============================== -->
  <aside class="lex-sidebar" id="lex-sidebar">

    <h3>Valgte ord</h3>
    <ul id="lex-selected">
      <li><em>Ingen ord valgt</em></li>
    </ul>

    <button id="lex-batch-explain" disabled>
      Forklar valgte ord
    </button>

    <hr>

    <h3>Ordforklaring</h3>
    <div id="lex-explanation">
      <em>Klikk på et ord i teksten</em>
    </div>

  </aside>

</div>

<script src="js/lex.js"></script>
</body>
</html>
