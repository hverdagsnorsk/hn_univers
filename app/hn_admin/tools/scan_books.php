<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| SCAN – Registrer tekster i database
|--------------------------------------------------------------------------
| Leser hn_books/books/{bok}/texts/*.html
| Registrerer nye tekster i tabellen `texts`
*/

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../_auth.php';

$books = get_books();

if (!$books) {
    exit("Ingen bøker funnet.\n");
}

$inserted = 0;

foreach ($books as $bookKey) {

    $texts = get_texts_for_book($bookKey);

    foreach ($texts as $text) {

        $textKey = $text['id'];

        $check = db()->prepare("
            SELECT id FROM texts
            WHERE book_key = :book AND text_key = :text
            LIMIT 1
        ");
        $check->execute([
            'book' => $bookKey,
            'text' => $textKey
        ]);

        if ($check->fetch()) {
            continue;
        }

        $stmt = db()->prepare("
            INSERT INTO texts (
                book_key,
                text_key,
                title,
                source_path,
                active,
                created_at
            ) VALUES (
                :book_key,
                :text_key,
                :title,
                :source_path,
                1,
                NOW()
            )
        ");

        $stmt->execute([
            'book_key'    => $bookKey,
            'text_key'    => $textKey,
            'title'       => 'Uten tittel (' . $textKey . ')',
            'source_path' => '/hn_books/books/' . $bookKey . '/texts/' . $textKey . '.html'
        ]);

        $inserted++;
    }
}

echo "Scan fullført. Nye tekster registrert: {$inserted}\n";
