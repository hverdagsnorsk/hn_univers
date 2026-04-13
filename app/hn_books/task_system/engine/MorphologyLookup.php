<?php
declare(strict_types=1);

final class MorphologyLookup
{

    private PDO $pdo;

    private array $nounCache = [];
    private array $verbCache = [];
    private array $adjCache  = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function normalize(string $lemma): string
    {
        return mb_strtolower(trim($lemma));
    }

    /*
    --------------------------------------------------
    NOUN FORMS
    --------------------------------------------------
    */

    public function getNounForms(string $lemma): ?array
    {

        $lemma = $this->normalize($lemma);

        if (isset($this->nounCache[$lemma])) {
            return $this->nounCache[$lemma];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                n.singular_indefinite,
                n.singular_definite,
                n.plural_indefinite,
                n.plural_definite,
                n.gender
            FROM lex_entries e
            JOIN lex_nouns n
                ON n.entry_id = e.id
            WHERE e.lemma = ?
            LIMIT 1
        ");

        $stmt->execute([$lemma]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->nounCache[$lemma] = $row;

        return $row;

    }

    /*
    --------------------------------------------------
    VERB FORMS
    --------------------------------------------------
    */

    public function getVerbForms(string $lemma): ?array
    {

        $lemma = $this->normalize($lemma);

        if (isset($this->verbCache[$lemma])) {
            return $this->verbCache[$lemma];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                v.infinitive,
                v.present,
                v.past,
                v.perfect
            FROM lex_entries e
            JOIN lex_verbs v
                ON v.entry_id = e.id
            WHERE e.lemma = ?
            LIMIT 1
        ");

        $stmt->execute([$lemma]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->verbCache[$lemma] = $row;

        return $row;

    }

    /*
    --------------------------------------------------
    ADJECTIVE FORMS
    --------------------------------------------------
    */

    public function getAdjectiveForms(string $lemma): ?array
    {

        $lemma = $this->normalize($lemma);

        if (isset($this->adjCache[$lemma])) {
            return $this->adjCache[$lemma];
        }

        $stmt = $this->pdo->prepare("
            SELECT
                a.positive,
                a.neuter,
                a.plural,
                a.comparative,
                a.superlative
            FROM lex_entries e
            JOIN lex_adjectives a
                ON a.entry_id = e.id
            WHERE e.lemma = ?
            LIMIT 1
        ");

        $stmt->execute([$lemma]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $this->adjCache[$lemma] = $row;

        return $row;

    }

    /*
    --------------------------------------------------
    GENERIC LOOKUP
    --------------------------------------------------
    */

    public function getForms(string $lemma,string $pos): ?array
    {

        return match($pos) {

            "noun" => $this->getNounForms($lemma),

            "verb" => $this->getVerbForms($lemma),

            "adjective" => $this->getAdjectiveForms($lemma),

            default => null

        };

    }

}