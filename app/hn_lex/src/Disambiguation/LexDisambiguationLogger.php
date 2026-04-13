<?php
declare(strict_types=1);

namespace HnLex\Disambiguation;

use PDO;

final class LexDisambiguationLogger
{
    public function __construct(private PDO $pdo) {}

    public function log(
        string $word,
        string $prev,
        string $next,
        int $entryId,
        int $senseId,
        int $score,
        int $candidateCount
    ): void {

        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO lex_disambiguation_logs (
                    word,
                    prev,
                    next,
                    entry_id,
                    sense_id,
                    score,
                    candidate_count,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $word,
                $prev,
                $next,
                $entryId,
                $senseId,
                $score,
                $candidateCount
            ]);

        } catch (\Throwable $e) {
            error_log('[DISAMBIG LOG FAIL] ' . $e->getMessage());
        }
    }
}