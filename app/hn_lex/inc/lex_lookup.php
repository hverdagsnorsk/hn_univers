<?php
declare(strict_types=1);

require_once __DIR__ . '/contracts/LexContract.php';

use HnLex\Contracts\LexContract;


/*
|------------------------------------------------------------------
| LEX LOOKUP – DYNAMISK (KONTRAKTBASERT)
|------------------------------------------------------------------
| - Matcher lemma
| - Matcher bøyninger via LexContract
| - Ingen hardkodede grammatikk-tabeller
| - Returnerer ARRAY av kandidater
| - Henter IKKE grammatikk her
|------------------------------------------------------------------
*/




function lookupLexCandidates(PDO $pdo, string $word, string $language = 'no'): array
{
    $word = mb_strtolower(trim($word));
    $word = preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $word);

    if ($word === '') {
        return [];
    }

    $candidates = [];

    /* ==========================================================
       1. Direkte lemma
    ========================================================== */

    $stmt = $pdo->prepare("
        SELECT e.*, wc.code AS ordklasse
        FROM lex_entries e
        JOIN lex_word_classes wc ON wc.id = e.word_class_id
        WHERE LOWER(e.lemma) = ?
          AND e.language = ?
    ");

    $stmt->execute([$word, $language]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $candidates[$row['id']] = $row;
    }

    /* ==========================================================
       2. BØYNINGER (DYNAMISK FRA KONTRAKT)
    ========================================================== */

    foreach (LexContract::GRAMMAR_SCHEMA as $ordklasse => $schema) {

        $table = $schema['table'] ?? null;
        if (!$table) {
            continue;
        }

        $fields = array_merge(
            $schema['required'] ?? [],
            $schema['optional'] ?? []
        );

        if (empty($fields)) {
            continue;
        }

        $conditions = [];
        $params     = [];

        foreach ($fields as $field) {

            // hopp over ikke-tekstfelter
            if (in_array($field, ['gender','countable','number_type','pronoun_type'], true)) {
                continue;
            }

            $conditions[] = "LOWER(TRIM(g.{$field})) = ?";
            $params[]     = $word;
        }

        if (empty($conditions)) {
            continue;
        }

        $sql = "
            SELECT e.*, wc.code AS ordklasse
            FROM {$table} g
            JOIN lex_entries e ON e.id = g.entry_id
            JOIN lex_word_classes wc ON wc.id = e.word_class_id
            WHERE (" . implode(' OR ', $conditions) . ")
              AND e.language = ?
        ";

        $params[] = $language;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $candidates[$row['id']] = $row;
        }
    }

    return array_values($candidates);
}
