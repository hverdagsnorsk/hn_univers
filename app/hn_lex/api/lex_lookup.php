<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Lex lookup – AUTORITATIV MOTOR (ALLE ORDKLASSER)
|--------------------------------------------------------------------------
| - Matcher lemma først
| - Matcher bøyningsformer i ALLE relevante tabeller
| - Returnerer lex_entries + ordklasse
|--------------------------------------------------------------------------
*/

function lookupLexEntry(PDO $pdo, string $word, string $language = 'no'): ?array
{
    $word = mb_strtolower(trim($word));
    $word = preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', $word);

    if ($word === '') {
        return null;
    }

    /* --------------------------------------------------
       1. Direkte lemma-treff
    -------------------------------------------------- */

    $stmt = $pdo->prepare(
        "SELECT e.*, wc.code AS ordklasse
         FROM lex_entries e
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         WHERE TRIM(e.lemma) = ?
           AND e.language = ?
         LIMIT 1"
    );

    $stmt->execute([$word, $language]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    /* --------------------------------------------------
       2. Grammatikk-tabeller (alle ordklasser)
    -------------------------------------------------- */

    $tables = [

        // SUBSTANTIV
        'lex_nouns' => [
            'singular_indefinite',
            'singular_definite',
            'plural_indefinite',
            'plural_definite'
        ],

        // VERB
        'lex_verbs' => [
            'infinitive',
            'present',
            'past',
            'perfect',
            'passive'
        ],

        // ADJEKTIV
        'lex_adjectives' => [
            'positive',
            'neuter',
            'plural',
            'definite',
            'comparative',
            'superlative',
            'superlative_definite'
        ],

        // PERSONLIG PRONOMEN
        'lex_pronouns_personal' => [
            'subject_form',
            'object_form'
        ],

        // EIENDOMSPRONOMEN
        'lex_pronouns_possessive' => [
            'base_form'
        ],

        // REFLEKSIVE
        'lex_pronouns_reflexive' => [
            'form'
        ],

        // DEMONSTRATIVE
        'lex_pronouns_demonstrative' => [
            'form'
        ],

        // DETERMINATIV
        'lex_determiners' => [
            'form_singular_m',
            'form_singular_f',
            'form_singular_n',
            'form_plural'
        ],
    ];

    foreach ($tables as $table => $fields) {

        $conditions = [];
        $params     = [];

        foreach ($fields as $field) {
            $conditions[] = "TRIM(g.{$field}) = ?";
            $params[]     = $word;
        }

        $sql = "
            SELECT e.*, wc.code AS ordklasse
            FROM {$table} g
            JOIN lex_entries e ON e.id = g.entry_id
            JOIN lex_word_classes wc ON wc.id = e.word_class_id
            WHERE (" . implode(' OR ', $conditions) . ")
              AND e.language = ?
            LIMIT 1
        ";

        $params[] = $language;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return $row;
        }
    }

    return null;
}
