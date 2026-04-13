<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/*
|--------------------------------------------------------------------------
| Scan alle bøker
|--------------------------------------------------------------------------
*/
$booksDir = realpath(__DIR__ . '/../books');
$books = array_filter(glob($booksDir . '/*'), 'is_dir');

foreach ($books as $bookPath) {

    $bookKey = basename($bookPath);
    $textsDir = $bookPath . '/texts';

    if (!is_dir($textsDir)) {
        continue;
    }

    $files = glob($textsDir . '/*.html');

    foreach ($files as $file) {

        $textKey = pathinfo($file, PATHINFO_FILENAME);

        // finnes teksten allerede?
        $check = $pdo->prepare("
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

        // registrer ny tekst
        $stmt = $pdo->prepare("
            INSERT INTO texts (
                book_key,
                text_key,
                title,
                source_path,
                active,
                created_at
            ) VALUES (
                :book,
                :text,
                :title,
                :path,
                1,
                NOW()
            )
        ");

        $stmt->execute([
            'book'  => $bookKey,
            'text'  => $textKey,
            'title' => 'Uten tittel (' . $textKey . ')',
            'path'  => '/hn_books/books/' . $bookKey . '/texts/' . $textKey . '.html'
        ]);
    }
}

echo "Scan fullført\n";
