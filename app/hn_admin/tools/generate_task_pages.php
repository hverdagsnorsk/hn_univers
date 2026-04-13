<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';

/** @var PDO $pdo */
$pdo = db();

$template = <<<HTML
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Oppgaver</title>
  <link rel="stylesheet" href="/hn_books/engine/tasks/tasks.css">
  <script src="/hn_books/engine/tasks/task_engine.js" defer></script>
</head>
<body>
<main id="task-root" data-text-id="%d"></main>
</body>
</html>
HTML;

$stmt = db()->query("SELECT id, book_key, text_key, source_path, active FROM texts WHERE active=1 ORDER BY book_key, id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base = realpath(__DIR__ . '/../../hn_books/books');
if (!$base) {
    echo "Fant ikke hn_books/books på forventet sti.\n";
    exit;
}

$created = 0;
$updated = 0;

foreach ($rows as $r) {
    $textId  = (int)$r['id'];
    $bookKey = (string)$r['book_key'];
    $textKey = (string)$r['text_key'];

    // Forventet plassering: hn_books/books/<book_key>/tasks/<text_key>.html
    $dir = $base . DIRECTORY_SEPARATOR . $bookKey . DIRECTORY_SEPARATOR . 'tasks';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $file = $dir . DIRECTORY_SEPARATOR . $textKey . '.html';
    $html = sprintf($template, $textId);

    if (!file_exists($file)) {
        file_put_contents($file, $html);
        $created++;
    } else {
        // Oppdater kun hvis innhold er forskjellig
        $old = (string)@file_get_contents($file);
        if (trim($old) !== trim($html)) {
            file_put_contents($file, $html);
            $updated++;
        }
    }
}

echo "Ferdig. Opprettet: {$created}, oppdatert: {$updated}\n";
