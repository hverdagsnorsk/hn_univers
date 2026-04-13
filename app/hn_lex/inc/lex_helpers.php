<?php
declare(strict_types=1);

require_once __DIR__ . '/contracts/LexContract.php';
require_once __DIR__ . '/lex_save.php';

/**
 * Hent eksisterende oppslag fra DB
 */
function lex_get_from_db(PDO $pdo, string $word, string $lang = 'no'): ?array
{
    $stmt = $pdo->prepare(
        "SELECT
            e.id,
            e.lemma,
            wc.code AS ordklasse,
            ex.explanation,
            ex.example
         FROM lex_entries e
         JOIN lex_word_classes wc ON wc.id = e.word_class_id
         LEFT JOIN lex_explanations ex
           ON ex.entry_id = e.id
          AND ex.level = 'A2'
          AND ex.language = ?
         WHERE e.lemma = ?
           AND e.language = ?
         LIMIT 1"
    );

    $stmt->execute([$lang, $word, $lang]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Hent og generer via AI
 * (Foreløpig stub – erstatt med ekte OpenAI-kall senere)
 *
 * Returnerer struktur klar for LexContract::fromAI()
 */
function lex_get_from_openai(string $word): array
{
    $word = mb_strtolower(trim($word));

    /*
    |------------------------------------------------------------
    | Midlertidig demo-grammatikk (kan erstattes av ekte AI)
    |------------------------------------------------------------
    */

    $ordklasse = 'verb';

    $grammar = [
        'infinitive' => $word,
        'present'    => $word . 'r',
        'past'       => $word . 'et',
        'perfect'    => 'har ' . $word . 'et',
        'passive'    => $word . 's',
    ];

    return [
        'lemma'     => $word,
        'ordklasse' => $ordklasse,
        'language'  => 'no',
        'grammar'   => $grammar,
        'explanations' => [
            'A2' => [
                'forklaring' => "Forklaring generert for «$word».",
                'example'    => "Jeg $word hver dag.",
                'source'     => 'ai'
            ]
        ]
    ];
}

/**
 * Ny autoritativ lagringsflyt
 */
function lex_generate_and_store(PDO $pdo, string $word): int
{
    $aiData   = lex_get_from_openai($word);
    $contract = LexContract::fromAI($aiData);

    return saveLexEntry($pdo, $contract);
}
