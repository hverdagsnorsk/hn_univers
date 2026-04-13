<?php
declare(strict_types=1);

namespace HnLex\Repository;

use PDO;

final class ExplanationRepository
{
    public function __construct(private PDO $pdo) {}

    /* ==========================================================
       UPSERT
    ========================================================== */

    public function upsertForSense(
        int $senseId,
        int $entryId,
        string $language,
        string $level,
        string $forklaring,
        ?string $example = null
    ): void {

        $forklaring = trim($forklaring);
        $level      = $this->normalizeLevel($level);

        if ($forklaring === '' || $level === '') {
            return;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO lex_explanations
            (sense_id, entry_id, language, level, explanation, example)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                explanation = VALUES(explanation),
                example     = VALUES(example)
        ");

        $stmt->execute([
            $senseId,
            $entryId,
            $language,
            $level,
            $forklaring,
            $example
        ]);
    }

    /* ==========================================================
       FIND BEST (KRITISK LOGIKK)
    ========================================================== */

    public function findBestForSense(
        int $senseId,
        string $language,
        string $preferredLevel
    ): ?array {

        $preferredLevel = $this->normalizeLevel($preferredLevel);

        $stmt = $this->pdo->prepare("
            SELECT level, explanation, example
            FROM lex_explanations
            WHERE sense_id = ?
              AND language = ?
        ");

        $stmt->execute([$senseId, $language]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        /* ---------- NORMALISER ---------- */

        foreach ($rows as &$row) {
            $row['level'] = $this->normalizeLevel($row['level']);
        }

        /* ---------- EXACT ---------- */

        foreach ($rows as $row) {
            if ($row['level'] === $preferredLevel) {
                return $row;
            }
        }

        /* ---------- NÆRMESTE NIVÅ ---------- */

        $priority = $this->levelPriority();

        $preferredIndex = $priority[$preferredLevel] ?? 999;

        usort($rows, function ($a, $b) use ($priority, $preferredIndex) {

            $aIndex = $priority[$a['level']] ?? 999;
            $bIndex = $priority[$b['level']] ?? 999;

            return abs($aIndex - $preferredIndex)
                 <=> abs($bIndex - $preferredIndex);
        });

        return $rows[0] ?? null;
    }

    /* ==========================================================
       FIND ALL
    ========================================================== */

    public function findAllForSense(
        int $senseId,
        string $language
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT level, explanation, example
            FROM lex_explanations
            WHERE sense_id = ?
              AND language = ?
        ");

        $stmt->execute([$senseId, $language]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /* ==========================================================
       GROUPED
    ========================================================== */

    public function findGroupedBySense(
        int $entryId,
        string $language
    ): array {

        $stmt = $this->pdo->prepare("
            SELECT sense_id, level, explanation, example
            FROM lex_explanations
            WHERE entry_id = ?
              AND language = ?
        ");

        $stmt->execute([$entryId, $language]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $grouped = [];

        foreach ($rows as $row) {

            $senseId = (int)($row['sense_id'] ?? 0);

            if ($senseId === 0) {
                continue;
            }

            $grouped[$senseId][] = [
                'level'       => $this->normalizeLevel($row['level']),
                'explanation' => $row['explanation'],
                'example'     => $row['example'],
            ];
        }

        return $grouped;
    }

    /* ==========================================================
       LEVEL SYSTEM (KRITISK)
    ========================================================== */

    private function normalizeLevel(string $level): string
    {
        return strtoupper(trim($level));
    }

    private function levelPriority(): array
    {
        return [
            'A1' => 1,
            'A2' => 2,
            'B1' => 3,
            'B2' => 4
        ];
    }
}