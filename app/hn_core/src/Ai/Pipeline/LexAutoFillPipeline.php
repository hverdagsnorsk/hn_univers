<?php
declare(strict_types=1);

namespace HnCore\Ai\Pipeline;

use PDO;

class LexAutoFillPipeline
{
    private PDO $pdo;

    public function __construct(PDO $pdoLex)
    {
        $this->pdo = $pdoLex;
    }

    public function extractWords(string $text): array
    {
        preg_match_all('/[a-zæøå]+/iu', $text, $m);

        $words = array_map(
            fn($w) => mb_strtolower(trim($w)),
            $m[0]
        );

        return array_values(array_unique($words));
    }

    private function exists(string $word): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM lex_entries WHERE lemma = ? LIMIT 1"
        );

        $stmt->execute([$word]);

        return (bool)$stmt->fetchColumn();
    }

    private function saveEntry(array $entry): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lex_entries
            (lemma, ordklasse, language)
            VALUES (?,?,?)
        ");

        $stmt->execute([
            $entry['lemma'],
            $entry['ordklasse'],
            $entry['language'] ?? 'no'
        ]);
    }

    public function processText(string $text): void
    {
        $words = $this->extractWords($text);

        foreach ($words as $word) {

            if ($this->exists($word)) {
                continue;
            }

            echo "Generating entry: {$word}\n";

            try {

                $entry = ai_generate_lex_entry($word);

                $this->saveEntry($entry);

            } catch (\Throwable $e) {

                echo "AI failed for {$word}: " . $e->getMessage() . "\n";
            }
        }
    }
}