<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;

final class LexAutoTuner
{
    public function __construct(private PDO $pdo) {}

    public function rebuildBoostTable(): void
    {
        $this->pdo->exec("TRUNCATE TABLE lex_boost_cache");

        $stmt = $this->pdo->query("
            SELECT 
                word,
                prev_word,
                next_word,
                chosen_sense_id,
                COUNT(*) as cnt
            FROM lex_disambiguation_log
            GROUP BY word, prev_word, next_word, chosen_sense_id
        ");

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $boost = (int) floor(log($row['cnt'] + 1) * 10);

            if ($boost > 30) {
                $boost = 30;
            }

            $insert = $this->pdo->prepare("
                INSERT INTO lex_boost_cache (
                    word,
                    prev_word,
                    next_word,
                    sense_id,
                    boost
                ) VALUES (?, ?, ?, ?, ?)
            ");

            $insert->execute([
                $row['word'],
                $row['prev_word'],
                $row['next_word'],
                $row['chosen_sense_id'],
                $boost
            ]);
        }
    }
}