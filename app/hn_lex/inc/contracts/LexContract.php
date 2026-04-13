<?php
declare(strict_types=1);

namespace HnLex\Contracts;

require_once __DIR__ . '/../LexTerminology.php';

use LexTerminology;

final class LexContract
{
    /* ==========================================================
       WORD CLASSES
    ========================================================== */

    public const WORD_CLASSES = [
        'substantiv',
        'verb',
        'adjektiv',
        'determinativ',
        'tallord',
        'adverb',
        'preposisjon',
        'konjunksjon',
        'subjunksjon',
        'infinitivsmerke',
        'interjeksjon',
        'pron_personal',
        'pron_possessive',
        'pron_reflexive',
        'pron_demonstrative',
        'numeral',
        'unknown'
    ];

    /* ==========================================================
       WORD CLASS ALIASES
    ========================================================== */

    private const WORD_CLASS_ALIASES = [
        'noun' => 'substantiv',
        'verb' => 'verb',
        'adjective' => 'adjektiv',
        'adverb' => 'adverb',
        'conjunction' => 'konjunksjon',
        'subjunction' => 'subjunksjon',
        'pronoun' => 'pron_personal',
        'number' => 'numeral',
        'unk' => 'unknown'
    ];

    /* ==========================================================
       NORMALIZATION
    ========================================================== */

    public static function normalizeWordClass(string $ordklasse): string
    {
        $ordklasse = strtolower(trim($ordklasse));

        if (isset(self::WORD_CLASS_ALIASES[$ordklasse])) {
            $ordklasse = self::WORD_CLASS_ALIASES[$ordklasse];
        }

        if (!in_array($ordklasse, self::WORD_CLASSES, true)) {
            return 'unknown';
        }

        return $ordklasse;
    }

    public static function isValidWordClass(string $ordklasse): bool
    {
        $ordklasse = self::normalizeWordClass($ordklasse);
        return in_array($ordklasse, self::WORD_CLASSES, true);
    }

    /* ==========================================================
       GRAMMAR SCHEMA (NY MODELL)
    ========================================================== */

    public const GRAMMAR_SCHEMA = [

        'substantiv' => [
            'required' => [
                'gender',
                'singular_indefinite',
                'singular_definite',
                'plural_indefinite',
                'plural_definite'
            ],
            'optional' => []
        ],

        'verb' => [
            'required' => [
                'infinitive',
                'present',
                'past',
                'perfect'
            ],
            'optional' => []
        ],

        'adjektiv' => [
            'required' => [
                'positive'
            ],
            'optional' => [
                'comparative',
                'superlative'
            ]
        ],

        'determinativ'      => ['required' => [], 'optional' => []],
        'adverb'            => ['required' => [], 'optional' => []],
        'preposisjon'       => ['required' => [], 'optional' => []],
        'konjunksjon'       => ['required' => [], 'optional' => []],
        'subjunksjon'       => ['required' => [], 'optional' => []],
        'infinitivsmerke'   => ['required' => [], 'optional' => []],
        'interjeksjon'      => ['required' => [], 'optional' => []],
        'pron_personal'     => ['required' => [], 'optional' => []],
        'pron_possessive'   => ['required' => [], 'optional' => []],
        'pron_reflexive'    => ['required' => [], 'optional' => []],
        'pron_demonstrative'=> ['required' => [], 'optional' => []],
        'numeral'           => ['required' => [], 'optional' => []],
        'unknown'           => ['required' => [], 'optional' => []],
    ];

    /* ==========================================================
       SCHEMA HELPERS
    ========================================================== */

    public static function getGrammarSchema(string $ordklasse): ?array
    {
        $ordklasse = self::normalizeWordClass($ordklasse);
        return self::GRAMMAR_SCHEMA[$ordklasse] ?? null;
    }

     public static function getGrammarTable(string $ordklasse): ?string
    {
    // Ny modell: vi bruker ikke egne tabeller lenger
    return null;
    }

    public static function getRequiredFields(string $ordklasse): array
    {
        $ordklasse = self::normalizeWordClass($ordklasse);
        return self::GRAMMAR_SCHEMA[$ordklasse]['required'] ?? [];
    }

    public static function getOptionalFields(string $ordklasse): array
    {
        $ordklasse = self::normalizeWordClass($ordklasse);
        return self::GRAMMAR_SCHEMA[$ordklasse]['optional'] ?? [];
    }

    public static function getAllowedFields(string $ordklasse): array
    {
        $ordklasse = self::normalizeWordClass($ordklasse);

        if (!isset(self::GRAMMAR_SCHEMA[$ordklasse])) {
            return [];
        }

        return array_values(array_unique(array_merge(
            self::GRAMMAR_SCHEMA[$ordklasse]['required'] ?? [],
            self::GRAMMAR_SCHEMA[$ordklasse]['optional'] ?? []
        )));
    }

    /* ==========================================================
       FROM DB
    ========================================================== */

    public static function fromDB(
        array $entryRow,
        ?array $grammar,
        array $explanations
    ): array {

        $rawClass =
            $entryRow['word_class']
            ?? $entryRow['ordklasse']
            ?? 'unknown';

        $ordklasse = self::normalizeWordClass((string)$rawClass);

        $grouped = [];

        foreach ($explanations as $row) {

            if (!is_array($row)) {
                continue;
            }

            $level = $row['level'] ?? 'A1';

            $grouped[$level] = [
                'forklaring' => $row['explanation'] ?? '',
                'example'    => $row['example'] ?? ''
            ];
        }

        return [
            'lemma'        => (string)($entryRow['lemma'] ?? ''),
            'language'     => (string)($entryRow['language'] ?? 'nb'),
            'ordklasse'    => $ordklasse,
            'grammar'      => $grammar ?? [],
            'explanations' => $grouped
        ];
    }

    /* ==========================================================
       TO PUBLIC
    ========================================================== */

    public static function toPublic(array $entry, string $level = 'A2'): array
    {
        $ordklasse = self::normalizeWordClass(
            (string)($entry['ordklasse'] ?? 'unknown')
        );

        $out = [
            'found'      => true,
            'lemma'      => $entry['lemma'] ?? '',
            'language'   => $entry['language'] ?? 'nb',
            'word_class' => $ordklasse,
            'word_class_label' => LexTerminology::label($ordklasse)
        ];

        $g = $entry['grammar'] ?? [];

        if ($g && is_array($g)) {

            $forms = [];

            switch ($ordklasse) {

                case 'substantiv':
                    $forms = [
                        $g['singular_indefinite'] ?? '',
                        $g['singular_definite'] ?? '',
                        $g['plural_indefinite'] ?? '',
                        $g['plural_definite'] ?? ''
                    ];
                    break;

                case 'verb':
                    $forms = [
                        $g['infinitive'] ?? '',
                        $g['present'] ?? '',
                        $g['past'] ?? '',
                        $g['perfect'] ?? ''
                    ];
                    break;

                case 'adjektiv':
                    $forms = [
                        $g['positive'] ?? '',
                        $g['comparative'] ?? '',
                        $g['superlative'] ?? ''
                    ];
                    break;

                default:
                    $forms = array_values(array_filter($g));
            }

            $forms = array_filter($forms);

            if ($forms) {
                $out['grammar'] = [[
                    'label' => 'Bøyning',
                    'value' => implode(', ', $forms)
                ]];
            }
        }

        $explanations = $entry['explanations'] ?? [];
        $exp = $explanations[$level] ?? ($explanations ? reset($explanations) : null);

        if ($exp) {
            if (!empty($exp['forklaring'])) {
                $out['forklaring'] = $exp['forklaring'];
            }

            if (!empty($exp['example'])) {
                $out['example'] = $exp['example'];
            }
        }

        return $out;
    }
}