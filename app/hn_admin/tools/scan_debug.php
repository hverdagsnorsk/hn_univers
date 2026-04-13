<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DEBUG – Scan bokstruktur (dry-run)
|--------------------------------------------------------------------------
| Leser hn_books/books/{bok}/texts/*.html
| Skriver IKKE til database
*/

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../_auth.php';

header('Content-Type: text/plain; charset=utf-8');

$BOOKS_DIR = HN_BOOKS_CONTENT;

if (!is_dir($BOOKS_DIR)) {
    http_response_code(500);
    exit("Fant ikke books-mappe: {$BOOKS_DIR}\n");
}

$books = get_books();

if (!$books) {
    exit("Ingen bøker funnet i {$BOOKS_DIR}\n");
}

foreach ($books as $book) {

    echo "=== BOK: {$book} ===\n";

    $texts = get_texts_for_book($book);

    if (!$texts) {
        echo "  - Ingen tekster funnet\n\n";
        continue;
    }

    echo "  - Tekster funnet: " . count($texts) . "\n";

    foreach ($texts as $t) {
        echo "    • {$t['id']}.html\n";
    }

    echo "\n";
}

echo "DONE (dry-run)\n";
