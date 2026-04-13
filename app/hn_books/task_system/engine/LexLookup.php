<?php
declare(strict_types=1);

final class LexLookup
{
    private PDO $pdo;

    private array $cache = [];

    public function __construct(PDO $pdoLex)
    {
        $this->pdo = $pdoLex;
    }

    /*
    --------------------------------------------------
    NORMALIZE TOKEN
    --------------------------------------------------
    */

    private function normalize(string $token): string
    {
        $token = mb_strtolower(trim($token));

        $token = preg_replace('/[^\p{L}\-]/u','',$token);

        return $token ?? '';
    }

    /*
    --------------------------------------------------
    LOOKUP WORDS
    --------------------------------------------------
    */

    public function lookupWords(array $tokens): array
    {

        if (!$tokens) {
            return [];
        }

        $tokens = array_map([$this,"normalize"],$tokens);

        $tokens = array_filter($tokens);

        $tokens = array_unique($tokens);

        $result = [];

        $missing = [];

        /*
        --------------------------------------------------
        CACHE CHECK
        --------------------------------------------------
        */

        foreach ($tokens as $token) {

            if (isset($this->cache[$token])) {

                $result[$token] = $this->cache[$token];

            } else {

                $missing[] = $token;

            }

        }

        if (!$missing) {
            return $result;
        }

        /*
        --------------------------------------------------
        CHUNK SQL (for store tekster)
        --------------------------------------------------
        */

        $chunks = array_chunk($missing,50);

        foreach ($chunks as $chunk) {

            $placeholders = implode(',',array_fill(0,count($chunk),'?'));

            $sql = "
            SELECT
                e.id,
                e.lemma,
                wc.code AS pos,

                n.gender,
                n.singular_indefinite,
                n.singular_definite,
                n.plural_indefinite,
                n.plural_definite,

                v.infinitive,
                v.present,
                v.past,
                v.perfect,

                a.positive,
                a.comparative,
                a.superlative

            FROM lex_entries e

            JOIN lex_word_classes wc
                ON wc.id = e.word_class_id

            LEFT JOIN lex_nouns n
                ON n.entry_id = e.id

            LEFT JOIN lex_verbs v
                ON v.entry_id = e.id

            LEFT JOIN lex_adjectives a
                ON a.entry_id = e.id

            WHERE e.lemma IN ($placeholders)
            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute($chunk);

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

                $lemma = $row["lemma"];

                $result[$lemma] = $row;

                $this->cache[$lemma] = $row;

            }

        }

        /*
        --------------------------------------------------
        RETURN STRUCTURE
        lemma => data
        --------------------------------------------------
        */

        return $result;

    }
/*
--------------------------------------------------
RANDOM LEX ENTRIES
Brukes av oppgavegeneratoren
--------------------------------------------------
*/

public function randomEntries(int $limit = 40): array
{

    $stmt = $this->pdo->prepare("
        SELECT
            e.id,
            e.lemma,
            wc.code AS pos,

            n.gender,
            n.singular_indefinite,
            n.singular_definite,
            n.plural_indefinite,
            n.plural_definite,

            v.infinitive,
            v.present,
            v.past,
            v.perfect,

            a.positive,
            a.comparative,
            a.superlative

        FROM lex_entries e

        JOIN lex_word_classes wc
            ON wc.id = e.word_class_id

        LEFT JOIN lex_nouns n
            ON n.entry_id = e.id

        LEFT JOIN lex_verbs v
            ON v.entry_id = e.id

        LEFT JOIN lex_adjectives a
            ON a.entry_id = e.id

        WHERE wc.code IN ('noun','verb','adj')

        ORDER BY RAND()

        LIMIT ?
    ");

    $stmt->bindValue(1,$limit,PDO::PARAM_INT);

    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($rows as $row){

        $lemma = $row["lemma"];

        $this->cache[$lemma] = $row;

    }

    return $rows;

}

}