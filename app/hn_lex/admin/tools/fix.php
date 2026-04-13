<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| FIX BENT LEMMAS (MERGE SCRIPT)
|--------------------------------------------------------------------------
| - Finds entries where lemma = definite form
| - Checks if correct lemma exists
| - Merges bad entry into good entry
| - Logs everything
| - Supports dry-run
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../../hn_core/inc/bootstrap.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$dryRun = true; // 🔁 SET TO false WHEN READY

echo "=== FIX BENT LEMMAS ===\n";
echo $dryRun ? "MODE: DRY RUN\n\n" : "MODE: LIVE\n\n";

/*
|--------------------------------------------------------------------------
| 1. Find bent lemma candidates
|--------------------------------------------------------------------------
*/

$sql = "
SELECT 
    e_bad.id   AS bad_id,
    e_bad.lemma AS bad_lemma,
    n.singular_indefinite AS correct_lemma,
    e_good.id  AS good_id
FROM lex_entries e_bad
JOIN lex_nouns n ON n.entry_id = e_bad.id
LEFT JOIN lex_entries e_good
    ON e_good.language = e_bad.language
    AND e_good.lemma = n.singular_indefinite
WHERE e_bad.language = 'no'
AND (
       e_bad.lemma = n.singular_definite
    OR e_bad.lemma = n.plural_definite
)
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
    echo "No bent lemma candidates found.\n";
    exit;
}

echo "Found " . count($rows) . " bent lemma candidates.\n\n";

foreach ($rows as $row) {

    $badId   = (int)$row['bad_id'];
    $badLemma = $row['bad_lemma'];
    $correctLemma = $row['correct_lemma'];
    $goodId  = $row['good_id'] ? (int)$row['good_id'] : null;

    echo "BAD:  #$badId  ($badLemma)\n";

    /*
    |--------------------------------------------------------------------------
    | CASE 1: Correct lemma exists → MERGE
    |--------------------------------------------------------------------------
    */

    if ($goodId) {

        echo " -> MERGE into #$goodId ($correctLemma)\n";

        if (!$dryRun) {

            $pdo->beginTransaction();

            try {

                $pdo->prepare("UPDATE lex_explanations SET entry_id = ? WHERE entry_id = ?")
                    ->execute([$goodId, $badId]);

                $pdo->prepare("UPDATE lex_senses SET entry_id = ? WHERE entry_id = ?")
                    ->execute([$goodId, $badId]);

                $pdo->prepare("DELETE FROM lex_nouns WHERE entry_id = ?")
                    ->execute([$badId]);

                $pdo->prepare("DELETE FROM lex_entries WHERE id = ?")
                    ->execute([$badId]);

                $pdo->commit();

            } catch (Throwable $e) {
                $pdo->rollBack();
                echo "   ERROR: " . $e->getMessage() . "\n";
                continue;
            }
        }

    }
    /*
    |--------------------------------------------------------------------------
    | CASE 2: Correct lemma does NOT exist → FIX LEMMA
    |--------------------------------------------------------------------------
    */
    else {

        echo " -> FIX lemma to '$correctLemma'\n";

        if (!$dryRun) {
            $pdo->prepare("UPDATE lex_entries SET lemma = ? WHERE id = ?")
                ->execute([$correctLemma, $badId]);
        }
    }

    echo "\n";
}

echo "DONE.\n";