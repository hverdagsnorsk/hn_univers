<?php
declare(strict_types=1);

namespace HnLex\Repository;

use PDO;

final class LexRepository
{
    public function __construct(private PDO $pdo) {}

    /* ==========================================================
       BASE SELECT (MED CLASS + SUBCLASS)
    ========================================================== */

    private function baseSelect(): string
    {
        return "
            SELECT
                e.id AS entry_id,
                e.lemma,

                wc.id   AS word_class_id,
                wc.code AS word_class_code,
                wc.name AS word_class_name,

                wsc.id   AS word_subclass_id,
                wsc.name AS word_subclass_name

            FROM lex_entries e

            JOIN lex_word_classes wc
                ON wc.id = e.word_class_id

            LEFT JOIN lex_word_subclasses wsc
                ON wsc.id = e.word_subclass_id
        ";
    }

    /* ==========================================================
       FIND ENTRY BY LEMMA
    ========================================================== */

    public function findEntry(string $lemma, string $language): ?array
    {
        $lemma = mb_strtolower(trim($lemma));

        if ($lemma === '') {
            return null;
        }

        $sql = $this->baseSelect() . "
            WHERE e.lemma = ?
              AND e.language = ?
              AND e.status = 'approved'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$lemma, $language]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ==========================================================
       FIND BY ID (PRIMARY)
    ========================================================== */

    public function findById(int $id, string $language): ?array
    {
        $sql = $this->baseSelect() . "
            WHERE e.id = ?
              AND e.language = ?
              AND e.status = 'approved'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id, $language]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /* ==========================================================
       FIND MULTIPLE
    ========================================================== */

    public function findByIds(array $ids, string $language): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = $this->baseSelect() . "
            WHERE e.id IN ($placeholders)
              AND e.language = ?
              AND e.status = 'approved'
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ...$ids,
            $language
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}