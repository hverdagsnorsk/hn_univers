<?php
declare(strict_types=1);

namespace HnLex\Repository;

use PDO;

final class SenseRepository
{
    public function __construct(private PDO $pdo) {}

    /* ==========================================================
       BASE SELECT (MED ENTRY FILTER)
    ========================================================== */

    private function baseSelect(): string
    {
        return "
            SELECT 
                s.id,
                s.entry_id,
                s.sense_order,
                s.embedding
            FROM lex_senses s
            JOIN lex_entries e ON e.id = s.entry_id
            WHERE e.status = 'approved'
        ";
    }

    /* ==========================================================
       HENT ALLE SENSES FOR ENTRY
    ========================================================== */

    public function findByEntry(int $entryId): array
    {
        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
                AND s.entry_id = ?
                ORDER BY s.sense_order ASC
            "
        );

        $stmt->execute([$entryId]);

        return $this->mapRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /* ==========================================================
       HENT PRIMÆR SENSE
    ========================================================== */

    public function findPrimaryByEntryId(int $entryId): ?array
    {
        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
                AND s.entry_id = ?
                ORDER BY s.sense_order ASC
                LIMIT 1
            "
        );

        $stmt->execute([$entryId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /* ==========================================================
       HENT ENKEL SENSE
    ========================================================== */

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
                AND s.id = ?
                LIMIT 1
            "
        );

        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /* ==========================================================
       OPPRETT NY SENSE
    ========================================================== */

    public function create(int $entryId, string $source = 'ai'): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO lex_senses (
                entry_id,
                sense_order,
                source,
                created_at
            )
            VALUES (
                ?,
                (
                    SELECT COALESCE(MAX(sense_order) + 1, 1)
                    FROM lex_senses
                    WHERE entry_id = ?
                ),
                ?,
                NOW()
            )
        ");

        $stmt->execute([$entryId, $entryId, $source]);

        return (int)$this->pdo->lastInsertId();
    }

    /* ==========================================================
       HENT SENSES FOR FLERE ENTRIES
    ========================================================== */

    public function findSensesForEntries(array $entryIds): array
    {
        $entryIds = array_values(array_unique(
            array_filter(array_map('intval', $entryIds))
        ));

        if (empty($entryIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($entryIds), '?'));

        $stmt = $this->pdo->prepare(
            $this->baseSelect() . "
                AND s.entry_id IN ($placeholders)
                ORDER BY s.entry_id, s.sense_order
            "
        );

        $stmt->execute($entryIds);

        $result = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

            $entryId = (int)$row['entry_id'];

            if (!isset($result[$entryId])) {
                $result[$entryId] = [];
            }

            $result[$entryId][] = $this->mapRow($row);
        }

        return $result;
    }

    /* ==========================================================
       MAPPING
    ========================================================== */

    private function mapRows(array $rows): array
    {
        return array_map(fn($r) => $this->mapRow($r), $rows);
    }

    private function mapRow(array $row): array
    {
        return [
            'id'          => (int)$row['id'],
            'entry_id'    => (int)$row['entry_id'],
            'sense_order' => (int)$row['sense_order'],

            // Lazy decode – kun hvis brukt
            'embedding'   => $this->decodeEmbedding($row['embedding']),
        ];
    }

    /* ==========================================================
       EMBEDDING
    ========================================================== */

    private function decodeEmbedding(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}