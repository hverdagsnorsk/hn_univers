<?php
declare(strict_types=1);

namespace HnLex\Service;

use PDO;
use HnLex\Contracts\LexContract;

final class LexFormsGenerator
{
    public function __construct(private PDO $pdo) {}

    public function generateForEntry(int $entryId, string $lemma, string $wordClass, array $grammar): void
    {
        $wordClass = LexContract::normalizeWordClass($wordClass);

        $forms = $this->buildForms($lemma, $wordClass, $grammar);

        if (empty($forms)) {
            return;
        }

        $this->insertForms($entryId, $forms);
    }

    private function buildForms(string $lemma, string $wordClass, array $g): array
    {
        $forms = [];

        switch ($wordClass) {

            case 'noun':
                $forms = [
                    $lemma,
                    $g['indefinite_singular'] ?? null,
                    $g['definite_singular'] ?? null,
                    $g['indefinite_plural'] ?? null,
                    $g['definite_plural'] ?? null
                ];
                break;

            case 'verb':
                $forms = [
                    $g['infinitive'] ?? $lemma,
                    $g['present'] ?? null,
                    $g['past'] ?? null,
                    $g['past_participle'] ?? null,
                    $g['imperative'] ?? null
                ];
                break;

            case 'adjective':
                $forms = [
                    $g['positive_mf'] ?? $lemma,
                    $g['positive_n'] ?? null,
                    $g['positive_pl'] ?? null,
                    $g['comparative'] ?? null,
                    $g['superlative'] ?? null
                ];
                break;

            default:
                $forms = [$lemma];
        }

        // Normaliser + fjern tomme
        $forms = array_filter(array_map(
            fn($f) => $this->normalize($f),
            $forms
        ));

        return array_values(array_unique($forms));
    }

    private function insertForms(int $entryId, array $forms): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO lex_forms (entry_id, form)
            VALUES (?, ?)
        ");

        foreach ($forms as $form) {
            $stmt->execute([$entryId, $form]);
        }
    }

    private function normalize(?string $word): string
    {
        if (!$word) return '';

        return mb_strtolower(trim($word));
    }
}