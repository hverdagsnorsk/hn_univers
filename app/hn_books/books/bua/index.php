<?php
declare(strict_types=1);

/* --------------------------------------------------
   UTF-8 header
-------------------------------------------------- */
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../engine/bootstrap.php';

$BOOK_KEY = 'bua';

/* --------------------------------------------------
   HENT TEKSTER
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.text_key,
        t.title,
        t.source_path
    FROM texts t
    WHERE t.book_key = :book
      AND t.active = 1
    ORDER BY t.text_key ASC
");
$stmt->execute(['book' => $BOOK_KEY]);
$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="no">
<head>

<meta charset="UTF-8">
<title>BUA – læring og praksis</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="css/style.css">

</head>

<body>

<div class="page">

<header class="hero hero-cover">
<div class="hero-overlay">
<h1>BUA – læring og praksis</h1>
<p class="hero-sub">Digital bok med tekster og oppgaver</p>
</div>
</header>

<main class="content">

<section class="toc">

<?php if (!$texts): ?>
<p>Ingen tekster funnet.</p>
<?php endif; ?>

<?php foreach ($texts as $t): ?>

<?php

/* --------------------------------------------------
   TREKK UT TEKSTNUMMER
-------------------------------------------------- */

$nr = null;

if (preg_match('/-(\d{3})$/', (string)$t['text_key'], $m)) {
    $nr = (int)$m[1];
}

/* --------------------------------------------------
   OPPGAVER FRA TASK SYSTEM
-------------------------------------------------- */

$taskJson = $_SERVER['DOCUMENT_ROOT']
    . "/hn_books/task_system/data/{$BOOK_KEY}/{$t['text_key']}.json";

$taskStatus = "none";
$taskCount = 0;

if (is_file($taskJson)) {

    $json = json_decode(file_get_contents($taskJson), true);

    if (!empty($json["tasks"])) {

        $taskStatus = "ready";
        $taskCount = count($json["tasks"]);

    } else {

        $taskStatus = "generating";

    }
}

?>

<article class="toc-card">

<div class="toc-meta">

<?php if ($nr !== null): ?>
<span class="toc-label">Tekst <?= $nr ?></span>
<?php endif; ?>

<?php if ($taskStatus === "ready"): ?>
<span class="task-status ready">🟢 Oppgaver klare</span>
<?php elseif ($taskStatus === "generating"): ?>
<span class="task-status generating">🟡 Genereres</span>
<?php else: ?>
<span class="task-status none">⚪ Ingen oppgaver</span>
<?php endif; ?>

</div>

<h3><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h3>

<div class="toc-actions">

<!-- LES TEKST -->

<a class="btn primary"
href="<?= htmlspecialchars($t['source_path'], ENT_QUOTES, 'UTF-8') ?>">
📘 Les tekst
</a>

<!-- OPPGAVER -->

<?php if ($taskStatus === "ready"): ?>

<a class="btn secondary"
href="/hn_books/task_system/pages/task_page.php?book=<?= $BOOK_KEY ?>&text=<?= htmlspecialchars($t['text_key'], ENT_QUOTES, 'UTF-8') ?>">
📝 Oppgaver (<?= $taskCount ?>)
</a>

<?php endif; ?>

<!-- QR-KODE -->

<?php

$qrPath = "/hn_books/qr/text_{$t['id']}.png";

if (is_file($_SERVER['DOCUMENT_ROOT'] . $qrPath)):

?>

<div class="qr-wrapper">

<img
src="<?= $qrPath ?>"
class="qr-small"
data-qr-large="<?= $qrPath ?>"
alt="QR-kode for ordinnlæring">

<div class="qr-label">Ordinnlæring</div>

</div>

<?php endif; ?>

</div>

</article>

<?php endforeach; ?>

</section>

</main>

<footer>© 2026 Hverdagsnorsk</footer>

</div>

<script>

document.addEventListener('DOMContentLoaded', () => {

document.querySelectorAll('.qr-small').forEach(qr => {

qr.addEventListener('click', e => {

e.stopPropagation();

document.querySelectorAll('.qr-small.is-zoomed')
.forEach(open => {

if (open !== qr) {
open.classList.remove('is-zoomed');
}

});

qr.classList.toggle('is-zoomed');

});

});

document.addEventListener('click', () => {

document.querySelectorAll('.qr-small.is-zoomed')
.forEach(qr => qr.classList.remove('is-zoomed'));

});

});

</script>

</body>
</html>