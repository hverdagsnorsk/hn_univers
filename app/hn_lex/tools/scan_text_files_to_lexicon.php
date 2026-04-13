<?php
declare(strict_types=1);

$root = dirname(__DIR__,2);

require_once $root.'/hn_core/inc/bootstrap.php';

$pdoMain = db('main');
$pdoLex  = db('lex');

echo "\n=============================\n";
echo "HN FILE SCANNER → LEXICON\n";
echo "=============================\n\n";

/* ======================================================
HENT TEKSTER
====================================================== */

$stmt = $pdoMain->query("
SELECT id, source_path
FROM texts
WHERE active = 1
");

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Tekster funnet: ".count($texts)."\n";

/* ======================================================
EKSISTERENDE LEMMA
====================================================== */

$stmt = $pdoLex->query("SELECT lemma FROM lex_entries");

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

    $pdoLex->exec("
    INSERT INTO lex_word_classes(code)
    VALUES ('unknown')
    ");

    $unknownClass = $pdoLex->lastInsertId();
}

/* ======================================================
INSERT (duplikatsikker)
====================================================== */

$insert = $pdoLex->prepare("
INSERT IGNORE INTO lex_entries
(lemma, word_class_id, language)
VALUES (?, ?, 'nb')
");

/* ======================================================
STOPPORD
====================================================== */

$stopWords = [
'og','i','på','av','for','til','med','som','det','den',
'de','et','en','er','var','har','hadde','skal','kan'
];

/* ======================================================
SCANNING
====================================================== */

$newCount = 0;

foreach ($texts as $t) {

    $file = $root.'/'.$t['source_path'];

    if (!file_exists($file)) {
        echo "Finner ikke fil: {$file}\n";
        continue;
    }

    $html = file_get_contents($file);

    /* fjern lydmarkører */
    $html = preg_replace('/\[\[.*?\]\]/','',$html);

    /* fjern html */
    $text = strip_tags($html);

    /* dekode html-entiteter */
    $text = html_entity_decode($text);

    $text = mb_strtolower($text);

    preg_match_all('/\p{L}+/u',$text,$matches);

    foreach ($matches[0] as $word) {

        if (mb_strlen($word) < 3) {
            continue;
        }

        if (in_array($word,$stopWords,true)) {
            continue;
        }

        if (isset($existingMap[$word])) {
            continue;
        }

        $insert->execute([
            $word,
            $unknownClass
        ]);

        if ($insert->rowCount() > 0) {

            $existingMap[$word] = true;

            $newCount++;

            if ($newCount % 100 === 0) {
                echo "Nye lemma: {$newCount}\n";
            }
        }
    }
}

echo "\nFerdig.\n";
echo "Nye oppslag opprettet: {$newCount}\n\n";