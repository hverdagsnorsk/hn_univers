<?php
declare(strict_types=1);

/**
 * Normaliser klikket ord til lex_entries-oppslag
 */
function normalizeClickedWord(PDO $pdo, string $word, string $lang = 'no'): ?array
{
    $word = mb_strtolower(trim($word));
    if ($word === '') {
        return null;
    }

    /* --------------------------------------------------
       1. Direkte lemma
    -------------------------------------------------- */
    $stmt = $pdo->prepare(
        "SELECT e.*, wc.code AS ordklasse
         FROM lex_entries e
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         WHERE e.lemma = ? AND e.language = ?
         LIMIT 1"
    );
    $stmt->execute([$word, $lang]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['clicked_form'] = $word;
        return $row;
    }

    /* --------------------------------------------------
       2. Verb – bøyde former
    -------------------------------------------------- */
    $stmt = $pdo->prepare(
        "SELECT e.*, wc.code AS ordklasse
         FROM lex_verbs v
         JOIN lex_entries e ON e.id = v.entry_id
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         WHERE ? IN (v.infinitive, v.present, v.past, v.perfect, v.passive)
           AND e.language = ?
         LIMIT 1"
    );
    $stmt->execute([$word, $lang]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['clicked_form'] = $word;
        return $row;
    }

    /* --------------------------------------------------
       3. Substantiv – bøyde former
    -------------------------------------------------- */
    $stmt = $pdo->prepare(
        "SELECT e.*, wc.code AS ordklasse
         FROM lex_nouns n
         JOIN lex_entries e ON e.id = n.entry_id
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         WHERE ? IN (
             n.singular_indefinite,
             n.singular_definite,
             n.plural_indefinite,
             n.plural_definite
         )
           AND e.language = ?
         LIMIT 1"
    );
    $stmt->execute([$word, $lang]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['clicked_form'] = $word;
        return $row;
    }

    /* --------------------------------------------------
       4. Adjektiv – gradbøyning
    -------------------------------------------------- */
    $stmt = $pdo->prepare(
        "SELECT e.*, wc.code AS ordklasse
         FROM lex_adjectives a
         JOIN lex_entries e ON e.id = a.entry_id
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         WHERE ? IN (a.positive, a.comparative, a.superlative)
           AND e.language = ?
         LIMIT 1"
    );
    $stmt->execute([$word, $lang]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['clicked_form'] = $word;
        return $row;
    }

    return null;
}
