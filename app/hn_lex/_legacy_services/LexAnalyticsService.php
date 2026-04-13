<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;

final class LexAnalyticsService
{
    public function __construct(private PDO $pdo) {}

    public function getTopAmbiguousWords(int $limit = 20): array
    {
        $stmt = $this->pdo->query("
            SELECT word, COUNT(*) as cnt
            FROM lex_disambiguation_log
            GROUP BY word
            ORDER BY cnt DESC
            LIMIT {$limit}
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSenseDistribution(string $word): array
    {
        $stmt = $this->pdo->prepare("
            SELECT chosen_sense_id, COUNT(*) as cnt
            FROM lex_disambiguation_log
            WHERE word = ?
            GROUP BY chosen_sense_id
            ORDER BY cnt DESC
        ");

        $stmt->execute([$word]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContextPatterns(int $senseId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT prev_word, next_word, COUNT(*) as cnt
            FROM lex_disambiguation_log
            WHERE chosen_sense_id = ?
            GROUP BY prev_word, next_word
            ORDER BY cnt DESC
            LIMIT 50
        ");

        $stmt->execute([$senseId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}