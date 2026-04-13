<?php
declare(strict_types=1);

/*
HN – SCAN TEXTS → FILL LEXICON
--------------------------------
Scanner alle tekster og legger
inn manglende lemma i lex_entries
*/

$root = dirname(__DIR__,2);

require_once $root.'/hn_core/inc/bootstrap.php';

$pdoMain = db('main');   // texts
$pdoLex  = db('lex');    // lex_entries

echo "\n=============================\n";
echo "HN LEXICON SCANNER\n";
echo "=============================\n\n";

/* ======================================================
HENT TEKSTER
====================================================== */

$stmt = $pdoMain->query("
SELECT id, text_content
FROM texts
WHERE active = 1
");

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Tekster funnet: ".count($texts)."\n";

/* ======================================================
HENT EKSISTERENDE LEMMA
====================================================== */

$stmt = $pdoLex->query("
SELECT lemma
FROM lex_entries
");

$existing = $stmt->fetchAll(PDO::FETCH_COLUMN);

$existingMap = [];

foreach ($existing as $lemma) {
    $existingMap[mb_strtolower($lemma)] = true;
}

echo "Eksisterende lemma: ".count($existingMap)."\n";

/* ======================================================
WORD CLASS
====================================================== */

$stmt = $pdoLex->query("
SELECT id
FROM lex_word_classes
WHERE code='unknown'
LIMIT 1
");

$unknownClass = $stmt->fetchColumn();

if (!$unknownClass) {

    echo "Oppretter word_class 'unknown'\n";

    $pdoLex->exec("
    INSERT INTO lex_word_classes(code)
    VALUES ('unknown')
    ");

    $unknownClass = $pdoLex->lastInsertId();
}

/* ======================================================
INSERT PREPARE
====================================================== */

$insert = $pdoLex->prepare("
INSERT INTO lex_entries
(lemma, word_class_id, language)
VALUES (?, ?, 'nb')
");

/* ======================================================
SCANNER
====================================================== */

$newCount = 0;

foreach ($texts as $t) {

    $text = mb_strtolower($t['text_content'] ?? '');

    preg_match_all('/\p{L}+/u', $text, $matches);

    foreach ($matches[0] as $word) {

        if (mb_strlen($word) < 3) {
            continue;
        }

        if (isset($existingMap[$word])) {
            continue;
        }

        if (preg_match('/^\p{Lu}/u',$word)) {
            continue;
        }

        $insert->execute([
            $word,
            $unknownClass
        ]);

        $existingMap[$word] = true;

        $newCount++;

        if ($newCount % 100 === 0) {
            echo "Nye lemma: {$newCount}\n";
        }
    }
}

echo "\nFerdig.\n";
echo "Nye oppslag opprettet: {$newCount}\n\n";