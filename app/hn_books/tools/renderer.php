<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| hn_books/tools/rerender_text_files.php
| Re-render alle tekstfiler til ny HN_Books-layout
|------------------------------------------------------------
*/

require_once __DIR__ . '/../engine/bootstrap.php';

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'];

/* ============================================================
   HENT TEKSTER
============================================================ */
$stmt = $pdo->query("
    SELECT id, book_key, text_key, source_path
    FROM texts
    WHERE active = 1
");

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$texts) {
    exit("Ingen tekster funnet\n");
}

/* ============================================================
   HJELPER: STRIP GAMMEL LAYOUT
============================================================ */
function strip_old_layout(string $html): string
{
    // Fjern <html>, <head>, <body> hvis de finnes
    $html = preg_replace('#<!DOCTYPE.*?>#is', '', $html);
    $html = preg_replace('#<html.*?>#is', '', $html);
    $html = preg_replace('#</html>#is', '', $html);
    $html = preg_replace('#<head.*?>.*?</head>#is', '', $html);
    $html = preg_replace('#<body.*?>#is', '', $html);
    $html = preg_replace('#</body>#is', '', $html);

    return trim($html);
}

/* ============================================================
   LOOP
============================================================ */
foreach ($texts as $t) {
    $path = $_SERVER['DOCUMENT_ROOT'] . $t['source_path'];

    if (!is_file($path)) {
        echo "⚠ Mangler fil: {$t['source_path']}\n";
        continue;
    }

    $original = file_get_contents($path);
    if ($original === false) {
        echo "⚠ Kan ikke lese: {$t['source_path']}\n";
        continue;
    }

    // Backup (én gang)
    $backup = $path . '.bak';
    if (!file_exists($backup)) {
        file_put_contents($backup, $original);
    }

    // Rens tekst
    $content = strip_old_layout($original);

    // Ny layout (reader forventer .reader)
    $rendered = <<<HTML
<div class="reader">
$content
</div>
HTML;

    file_put_contents($path, $rendered);

    echo "✔ Rerendret: {$t['book_key']} / {$t['text_key']}\n";
}

echo "FERDIG – alle tekster er oppdatert\n";
