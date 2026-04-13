<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------
 | engine/scan_debug.php
 |------------------------------------------------------------
 | DEBUG: Scanner books/ og lister hvilke tekster som finnes
 | (ingen database, ingen registrering – bare verifisering)
 */

$ROOT = realpath(__DIR__ . '/..');
$BOOKS_DIR = $ROOT . '/books';

header('Content-Type: text/plain; charset=utf-8');

if (!is_dir($BOOKS_DIR)) {
    http_response_code(500);
    exit("Fant ikke books-mappe: {$BOOKS_DIR}\n");
}

$books = glob($BOOKS_DIR . '/*', GLOB_ONLYDIR) ?: [];

if (!$books) {
    exit("Ingen bøker funnet i {$BOOKS_DIR}\n");
}

foreach ($books as $bookPath) {
    $bookKey = basename($bookPath);
    $metaFile = $bookPath . '/book.json';
    $textsDir = $bookPath . '/texts';

    echo "=== BOOK: {$bookKey} ===\n";

    if (!file_exists($metaFile)) {
        echo "  - book.json: MANGLER\n";
    } else {
        echo "  - book.json: OK\n";
    }

    if (!is_dir($textsDir)) {
        echo "  - texts/: MANGLER\n\n";
        continue;
    }

    $files = glob($textsDir . '/*.html') ?: [];
    echo "  - tekster funnet: " . count($files) . "\n";
    foreach ($files as $f) {
        echo "    • " . basename($f) . "\n";
    }
    echo "\n";
}

echo "DONE\n";
