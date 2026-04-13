<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| REFRACTOR: Norwegian → English word_class
|------------------------------------------------------------
| - Scanner PHP files
| - Replaces ONLY safe patterns
| - Creates .bak backup
|------------------------------------------------------------
*/

$root = dirname(__DIR__, 2); // /www

$map = [
    'substantiv' => 'noun',
    'adjektiv' => 'adjective',
    'determinativ' => 'determiner',
    'tallord' => 'numeral',
    'preposisjon' => 'preposition',
    'konjunksjon' => 'conjunction',
    'subjunksjon' => 'subjunction',
    'infinitivsmerke' => 'infinitive_marker',
    'interjeksjon' => 'interjection',
    'pronomen' => 'pronoun'
];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root)
);

$changedFiles = 0;

foreach ($iterator as $file) {

    if ($file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $content = file_get_contents($path);

    $original = $content;

    foreach ($map as $no => $en) {

        // 🔒 Kun if-sammenligninger
        $content = preg_replace(
            "/(===|==)\s*['\"]{$no}['\"]/",
            "$1 '{$en}'",
            $content
        );

        $content = preg_replace(
            "/['\"]{$no}['\"]\s*(===|==)/",
            "'{$en}' $1",
            $content
        );
    }

    if ($content !== $original) {

        copy($path, $path . '.bak');

        file_put_contents($path, $content);

        echo "UPDATED: {$path}\n";

        $changedFiles++;
    }
}

echo "\nDone. Files changed: {$changedFiles}\n";