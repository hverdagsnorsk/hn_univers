<?php
declare(strict_types=1);

class TaskEngine
{
    private PDO $pdoLex;

    private int $maxTasksPerSentence = 2;
    private int $maxTasksPerLemma = 2;

    private array $sentenceUsage = [];
    private array $lemmaUsage = [];

    private array $priorityPOS = [
        'prep','subj','verb','noun'
    ];

    public function __construct(PDO $pdoLex)
    {
        $this->pdoLex = $pdoLex;
    }

    /* --------------------------------------------------
       PUBLIC API
    -------------------------------------------------- */

    public function generateTasks(string $text): array
    {
        $sentences = $this->splitSentences($text);

        $tasks = [];

        foreach ($sentences as $sentence) {

            $analysis = $this->analyseSentence($sentence);

            foreach ($analysis as $token) {

                $lemma = $token['lemma'];
                $pos   = $token['pos'];

                if (!in_array($pos,$this->priorityPOS)) {
                    continue;
                }

                if (($this->lemmaUsage[$lemma] ?? 0) >= $this->maxTasksPerLemma) {
                    continue;
                }

                $task = $this->buildTask($sentence,$token);

                if ($task) {

                    $tasks[] = $task;

                    $this->lemmaUsage[$lemma] =
                        ($this->lemmaUsage[$lemma] ?? 0) + 1;

                }

            }

            /* setningsoppgaver */

            if (rand(0,3) === 1) {
                $tasks[] = $this->wordOrderTask($sentence);
            }

            if (rand(0,4) === 1) {
                $tasks[] = $this->trueFalseTask($sentence);
            }

        }

        return $tasks;
    }

    /* --------------------------------------------------
       SENTENCE ANALYSIS
    -------------------------------------------------- */

    private function analyseSentence(string $sentence): array
    {
        $words = $this->tokenize($sentence);

        $analysis = [];

        foreach ($words as $word) {

            $entry = $this->lookupLex($word);

            if (!$entry) {
                continue;
            }

            $analysis[] = [
                'word'  => $word,
                'lemma' => $entry['lemma'],
                'pos'   => $entry['pos']
            ];

        }

        return $analysis;
    }

    /* --------------------------------------------------
       SENTENCE SPLIT
    -------------------------------------------------- */

    private function splitSentences(string $text): array
    {
        $parts = preg_split('/(?<=[.!?])\s+/u', trim($text));

        return array_filter($parts);
    }

    /* --------------------------------------------------
       TOKENIZER
    -------------------------------------------------- */

    private function tokenize(string $sentence): array
    {
        $sentence = preg_replace('/[.,!?;:]/u','',$sentence);

        return preg_split('/\s+/u',$sentence);
    }

    /* --------------------------------------------------
       LEX LOOKUP
    -------------------------------------------------- */

    private function lookupLex(string $word): ?array
    {
        $stmt = $this->pdoLex->prepare("
            SELECT lemma,pos
            FROM lex_entries
            WHERE lemma = ?
               OR form = ?
            LIMIT 1
        ");

        $stmt->execute([$word,$word]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /* --------------------------------------------------
       TASK BUILDER
    -------------------------------------------------- */

    private function buildTask(string $sentence,array $token): ?array
    {
        switch ($token['pos']) {

            case 'prep':
                return $this->prepositionTask($sentence,$token['word']);

            case 'verb':
                return $this->verbTask($token['lemma']);

            case 'noun':
                return $this->nounTask($token['lemma']);

            case 'subj':
                return $this->subjunctionTask($sentence,$token['word']);

            default:
                return null;
        }
    }

    /* --------------------------------------------------
       PREPOSITION
    -------------------------------------------------- */

    private function prepositionTask(string $sentence,string $word): array
    {
        $masked = preg_replace(
            '/\b'.preg_quote($word,'/').'\b/u',
            '____',
            $sentence,
            1
        );

        return [
            'type'=>'preposition',
            'sentence'=>$masked,
            'answer'=>$word
        ];
    }

    /* --------------------------------------------------
       VERB
    -------------------------------------------------- */

    private function verbTask(string $lemma): array
    {
        $stmt = $this->pdoLex->prepare("
            SELECT infinitive,present,past,perfect
            FROM lex_verbs
            WHERE lemma=?
            LIMIT 1
        ");

        $stmt->execute([$lemma]);

        $forms = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'type'=>'verb_inflection',
            'lemma'=>$lemma,
            'forms'=>$forms
        ];
    }

    /* --------------------------------------------------
       NOUN
    -------------------------------------------------- */

    private function nounTask(string $lemma): array
    {
        $stmt = $this->pdoLex->prepare("
            SELECT indefinite,definite,plural
            FROM lex_nouns
            WHERE lemma=?
            LIMIT 1
        ");

        $stmt->execute([$lemma]);

        $forms = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'type'=>'noun_inflection',
            'lemma'=>$lemma,
            'forms'=>$forms
        ];
    }

    /* --------------------------------------------------
       SUBJUNCTION
    -------------------------------------------------- */

    private function subjunctionTask(string $sentence,string $word): array
    {
        return [
            'type'=>'subjunction',
            'sentence'=>$sentence,
            'answer'=>$word
        ];
    }

    /* --------------------------------------------------
       WORD ORDER
    -------------------------------------------------- */

    private function wordOrderTask(string $sentence): array
    {
        $words = $this->tokenize($sentence);

        shuffle($words);

        return [
            'type'=>'word_order',
            'scrambled'=>implode(' ',$words),
            'answer'=>$sentence
        ];
    }

    /* --------------------------------------------------
       TRUE FALSE
    -------------------------------------------------- */

    private function trueFalseTask(string $sentence): array
    {
        return [
            'type'=>'true_false',
            'statement'=>$sentence,
            'task'=>'Er denne setningen riktig?'
        ];
    }
}