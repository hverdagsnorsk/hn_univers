<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';

/*
|----------------------------------------------------------
| REPAIR GRAMMAR BATCH
|----------------------------------------------------------
| - Finner oppslag uten grammatikk
| - Kaller AI på nytt
| - Lagrer via saveLexEntry()
*/

$sql = "
SELECT e.id, e.lemma, wc.code
FROM lex_entries e
JOIN lex_word_classes wc ON wc.id = e.word_class_id
LEFT JOIN lex_verbs v ON v.entry_id = e.id
LEFT JOIN lex_nouns n ON n.entry_id = e.id
LEFT JOIN lex_adjectives a ON a.entry_id = e.id
WHERE
(
    (wc.code = 'verb' AND (v.present IS NULL OR v.past IS NULL OR v.perfect IS NULL))
 OR (wc.code = 'substantiv' AND (n.singular_indefinite IS NULL OR n.plural_indefinite IS NULL))
 OR (wc.code = 'adjektiv' AND (a.positive IS NULL OR a.comparative IS NULL))
)
LIMIT 20
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {

    echo "Reparerer: {$row['lemma']} ({$row['code']})\n";

    // Din eksisterende AI-kjede her:
    $aiData = generateLexFromAI($row['lemma'], $row['code']);

    $contract = LexContract::fromAI($aiData);

    saveLexEntry($pdo, $contract);
}

echo "Ferdig.\n";
