<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;

final class LexLearningService
{
    /** cache per request */
    private static array $cache = [];

    public function __construct(private PDO $pdo) {}

    /* ==========================================================
       BOOST (CACHE-BASED)
    ========================================================== */

    public function getBoost(
        string $word,
        string $prev,
        string $next,
        int $senseId
    ): int {

        if ($word === '' || $senseId <= 0) {
            return 0;
        }

        $word = $this->norm($word);
        $prev = $this->norm($prev);
        $next = $this->norm($next);

        $cacheKey = md5($word . '|' . $prev . '|' . $next . '|' . $senseId);

        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        /* ======================================================
           1. BOOST CACHE (PRIMARY SOURCE)
        ====================================================== */

        $stmt = $this->pdo->prepare("
            SELECT boost
            FROM lex_boost_cache
            WHERE word = ?
              AND sense_id = ?
              AND (
                    (prev_word = ? AND next_word = ?)
                 OR (prev_word = ? AND next_word = '')
                 OR (prev_word = '' AND next_word = ?)
                 OR (prev_word = '' AND next_word = '')
              )
            ORDER BY boost DESC
            LIMIT 1
        ");

        $stmt->execute([
            $word,
            $senseId,
            $prev, $next,
            $prev,
            $next
        ]);

        $boost = $stmt->fetchColumn();

        if ($boost !== false) {
            $boost = (int)$boost;
            self::$cache[$cacheKey] = $boost;
            return $boost;
        }

        /* ======================================================
           2. FALLBACK (LOG TABLE – MIDLERITIDIG)
        ====================================================== */

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as cnt
            FROM lex_disambiguation_log
            WHERE word = ?
              AND chosen_sense_id = ?
              AND (
                    prev_word = ?
                 OR next_word = ?
                 OR prev_word = ''
                 OR next_word = ''
              )
        ");

        $stmt->execute([
            $word,
            $senseId,
            $prev,
            $next
        ]);

        $count = (int)$stmt->fetchColumn();

        /* ======================================================
           3. SCALING
        ====================================================== */

        $boost = (int) floor(log($count + 1) * 8);

        if ($boost > 25) {
            $boost = 25;
        }

        self::$cache[$cacheKey] = $boost;

        return $boost;
    }

    /* ==========================================================
       NORMALIZATION
    ========================================================== */

    private function norm(string $text): string
    {
        return mb_strtolower(trim($text));
    }
}