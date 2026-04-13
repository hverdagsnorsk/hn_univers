<?php
declare(strict_types=1);

namespace HnLex\Repository;

use PDO;

final class CandidatesRepository
{
    private static array $cache = [];

    public function __construct(private PDO $pdo) {}

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public function findCandidatesByForm(string $form, string $language): array
    {
        $form = $this->normalize($form);

        if ($form === '') {
            return [];
        }

        $key = $language . '|' . $form;

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        /* ======================================================
           1. PRIMÆR: lex_forms
        ====================================================== */

        $stmt = $this->pdo->prepare("
            SELECT 
                f.entry_id,
                e.lemma,
                wc.code AS word_class,
                s.embedding
            FROM lex_forms f
            JOIN lex_entries e ON e.id = f.entry_id
            JOIN lex_word_classes wc ON wc.id = e.word_class_id
            JOIN lex_senses s ON s.entry_id = e.id
            WHERE f.form = ?
              AND e.language = ?
              AND e.status = 'approved'
        ");

        $stmt->execute([$form, $language]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        /* ======================================================
           🔥 FALLBACK: lemma direkte
        ====================================================== */

        if (empty($rows)) {

            $stmt = $this->pdo->prepare("
                SELECT 
                    e.id AS entry_id,
                    e.lemma,
                    wc.code AS word_class,
                    s.embedding
                FROM lex_entries e
                JOIN lex_word_classes wc ON wc.id = e.word_class_id
                JOIN lex_senses s ON s.entry_id = e.id
                WHERE LOWER(e.lemma) = ?
                  AND e.language = ?
                  AND e.status = 'approved'
            ");

            $stmt->execute([$form, $language]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        /* ======================================================
           EMBEDDING DECODE
        ====================================================== */

        foreach ($rows as &$row) {
            $row['embedding'] = $row['embedding']
                ? json_decode($row['embedding'], true)
                : [];
        }

        return self::$cache[$key] = $rows;
    }

    private function normalize(string $word): string
    {
        return preg_replace('/^[^\p{L}]+|[^\p{L}]+$/u', '', mb_strtolower(trim($word)));
    }
}