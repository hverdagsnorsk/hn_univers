<?php
declare(strict_types=1);

final class TaskEngine
{
    private PDO $pdo;

    public function __construct(PDO $pdoLex)
    {
        $this->pdo = $pdoLex;
    }

    public function analyzeText(string $text): array
    {
        $tokens = preg_split('/[^\p{L}]+/u', $text);

        $tokens = array_filter($tokens);

        $analysis = [];

        foreach ($tokens as $token) {

            $lemma = mb_strtolower($token);

            $stmt = $this->pdo->prepare("
                SELECT lemma, pos
                FROM lex_entries
                WHERE lemma = ?
                LIMIT 1
            ");

            $stmt->execute([$lemma]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                continue;
            }

            $analysis[] = [
                "word" => $token,
                "lemma" => $row["lemma"],
                "pos" => $row["pos"]
            ];
        }

        return $analysis;
    }

    public function generateTasks(array $analysis): array
    {
        $tasks = [];

        foreach ($analysis as $a) {

            if ($a["pos"] === "verb") {

                $tasks[] = [
                    "type" => "verb",
                    "verb" => $a["lemma"],
                    "question" => "Bøy verbet i presens"
                ];
            }

            if ($a["pos"] === "noun") {

                $tasks[] = [
                    "type" => "article",
                    "word" => $a["lemma"],
                    "options" => ["en","ei","et"]
                ];
            }
        }

        return $tasks;
    }
}