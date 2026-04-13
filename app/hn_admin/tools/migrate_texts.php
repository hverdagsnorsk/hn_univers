<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| migrate_texts.php
|--------------------------------------------------------------------------
| ÉNGANGSSCRIPT
| Migrerer eksisterende lesebok-HTML til ny reader-engine
| - Leser HTML
| - Ekstraherer avsnitt
| - Regenererer HTML via generate_text_html()
| - Overskriver filen
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../bootstrap.php';

require_once __DIR__ . '/../text_generate.php';

set_time_limit(0);

echo '<pre>';

echo "HN Books – migrering startet\n\n";

/* --------------------------------------------------
   Hent tekster
-------------------------------------------------- */
$stmt = db()->query("
    SELECT
        id,
        book_key,
        text_key,
        title,
        source_path,
        active
    FROM texts
    WHERE active = 1
    ORDER BY book_key, text_key
");

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$texts) {
    exit("Ingen tekster funnet.\n");
}

/* --------------------------------------------------
   Backup-map
-------------------------------------------------- */
$backupBase = $_SERVER['DOCUMENT_ROOT']
    . '/hn_books/_backup_texts_' . date('Ymd_His');

mkdir($backupBase, 0775, true);

/* --------------------------------------------------
   Hjelpefunksjon: hent avsnitt fra gammel HTML
-------------------------------------------------- */
function extract_paragraphs(string $html): array
{
    libxml_use_internal_errors(true);

    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

    $paras = [];
    foreach ($dom->getElementsByTagName('p') as $p) {
        $text = trim($p->textContent);
        if ($text !== '') {
            $paras[] = $text;
        }
    }

    return $paras;
}

/* --------------------------------------------------
   Kjør migrering
-------------------------------------------------- */
$ok = 0;
$fail = 0;

foreach ($texts as $t) {

    $path = $_SERVER['DOCUMENT_ROOT'] . $t['source_path'];

    echo "▶ {$t['text_key']} ... ";

    if (!is_file($path)) {
        echo "HOPPET OVER (fil mangler)\n";
        $fail++;
        continue;
    }

    /* Backup */
    $backupDir = $backupBase . '/' . $t['book_key'];
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }

    copy($path, $backupDir . '/' . basename($path));

    /* Les gammel HTML */
    $oldHtml = file_get_contents($path);
    if ($oldHtml === false) {
        echo "FEIL (kunne ikke lese)\n";
        $fail++;
        continue;
    }

    /* Ekstraher avsnitt */
    $paragraphs = extract_paragraphs($oldHtml);

    if (!$paragraphs) {
        echo "FEIL (ingen avsnitt funnet)\n";
        $fail++;
        continue;
    }

    $rawText = implode("\n\n", $paragraphs);

    /* Regenerer */
    try {
        generate_text_html(
            bookKey: $t['book_key'],
            textKey: $t['text_key'],
            title: $t['title'],
            rawText: $rawText,
            level: null
        );

        echo "OK\n";
        $ok++;

    } catch (Throwable $e) {
        echo "FEIL ({$e->getMessage()})\n";
        $fail++;
    }
}

echo "\n---\n";
echo "Ferdig.\n";
echo "OK:   {$ok}\n";
echo "FEIL: {$fail}\n";
echo "Backup: {$backupBase}\n";

echo '</pre>';
