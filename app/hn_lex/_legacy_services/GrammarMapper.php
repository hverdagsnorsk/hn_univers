<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;

final class GrammarMapper
{
    public function __construct(private PDO $pdo) {}

    public function map(int $entryId, string $wordClass, array $grammar): void
    {
        foreach ($grammar as $key => $value) {

            if (!$value) continue;

            /* =========================
               1. SAVE GRAMMAR
            ========================= */

            $this->pdo->prepare("
                INSERT INTO lex_grammar (entry_id, word_class, `key`, `value`)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $entryId,
                $wordClass,
                $key,
                $value
            ]);

            /* =========================
               2. SAVE FORMS (CRITICAL)
            ========================= */

            $forms = $this->extractForms($value);

            foreach ($forms as $form) {

                if (!$form) continue;

                $this->pdo->prepare("
                    INSERT IGNORE INTO lex_forms (form, entry_id, word_class, field)
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    mb_strtolower($form),
                    $entryId,
                    $wordClass,
                    $key
                ]);
            }
        }
    }

    private function extractForms(string $value): array
    {
        // splitter "en jobb" → ["jobb"]
        // splitter "har arbeidet" → ["arbeidet"]

        $value = trim($value);

        // fjern artikler
        $value = preg_replace('/^(en|ei|et)\s+/u', '', $value);

        // splitt på mellomrom (tar siste ord)
        $parts = preg_split('/\s+/u', $value);

        return [end($parts)];
    }
}