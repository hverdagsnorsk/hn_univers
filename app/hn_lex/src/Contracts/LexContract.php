<?php
declare(strict_types=1);

namespace HnLex\Contracts;

use HnLex\Terminology\LexTerminology;

final class LexContract
{
    public const WORD_CLASSES = [
        'noun',
        'verb',
        'adjective',
        'determiner',
        'numeral',
        'adverb',
        'preposition',
        'conjunction',
        'subjunction',
        'infinitive_marker',
        'interjection',
        'pronoun',
        'unknown'
    ];

    /* ==========================================================
       ?? SUBCLASSES (NY)
    ========================================================== */

    public const SUBCLASSES = [
        'pronoun' => [
            'personal',
            'possessive',
            'demonstrative',
            'reflexive',
            'interrogative',
            'relative',
            'indefinite'
        ],
        'determiner' => [
            'article',
            'possessive',
            'demonstrative',
            'quantifier'
        ]
    ];

    /* ==========================================================
       ALIASES (NOR + ENG)
    ========================================================== */

    private const WORD_CLASS_ALIASES = [

        'substantiv' => 'noun',
        'substantiver' => 'noun',

        'verb' => 'verb',

        'adjektiv' => 'adjective',

        'adverb' => 'adverb',

        'konjunksjon' => 'conjunction',
        'bindeord' => 'conjunction',

        'subjunksjon' => 'subjunction',

        'pronomen' => 'pronoun',

        'determinativ' => 'determiner',

        'tallord' => 'numeral',

        'preposisjon' => 'preposition',
        'prep' => 'preposition',

        'infinitivsmerke' => 'infinitive_marker',

        'interjeksjon' => 'interjection',

        'ukjent' => 'unknown',

        'noun' => 'noun',
        'verb' => 'verb',
        'adjective' => 'adjective',
        'adverb' => 'adverb',
        'conjunction' => 'conjunction',
        'subjunction' => 'subjunction',
        'pronoun' => 'pronoun',
        'determiner' => 'determiner',
        'number' => 'numeral',
        'numeral' => 'numeral',
        'preposition' => 'preposition',
        'unknown' => 'unknown',
        'unk' => 'unknown'
    ];

    /* ==========================================================
       NORMALIZATION
    ========================================================== */

    public static function normalizeWordClass(string $wordClass): string
    {
        if ($wordClass === '') {
            return 'unknown';
        }

        $wordClass = mb_strtolower(trim($wordClass), 'UTF-8');
        $wordClass = preg_replace('/\s+/u', '', $wordClass);
        $wordClass = preg_replace('/[^a-z_]/u', '', $wordClass);

        if (isset(self::WORD_CLASS_ALIASES[$wordClass])) {
            return self::WORD_CLASS_ALIASES[$wordClass];
        }

        if (in_array($wordClass, self::WORD_CLASSES, true)) {
            return $wordClass;
        }

        return 'unknown';
    }

    public static function isValidWordClass(string $wordClass): bool
    {
        return in_array(
            self::normalizeWordClass($wordClass),
            self::WORD_CLASSES,
            true
        );
    }

    /* ==========================================================
       ?? SUBCLASS NORMALIZATION (NY)
    ========================================================== */

    public static function normalizeSubclass(
        string $wordClass,
        ?string $subclass
    ): ?string {

        if (!$subclass) {
            return null;
        }

        $subclass = mb_strtolower(trim($subclass), 'UTF-8');
        $subclass = preg_replace('/\s+/u', '', $subclass);
        $subclass = preg_replace('/[^a-z_]/u', '', $subclass);

        $wordClass = self::normalizeWordClass($wordClass);

        if (!isset(self::SUBCLASSES[$wordClass])) {
            return null;
        }

        return in_array($subclass, self::SUBCLASSES[$wordClass], true)
            ? $subclass
            : null;
    }

    public static function isValidSubclass(
        string $wordClass,
        ?string $subclass
    ): bool {

        return self::normalizeSubclass($wordClass, $subclass) !== null;
    }

    /* ==========================================================
       GRAMMAR
    ========================================================== */

    public const GRAMMAR_SCHEMA = [

        'noun' => [
            'required' => [
                'gender',
                'indefinite_singular',
                'definite_singular',
                'indefinite_plural',
                'definite_plural'
            ],
            'optional' => []
        ],

        'verb' => [
            'required' => [
                'infinitive',
                'present',
                'past',
                'past_participle'
            ],
            'optional' => ['imperative']
        ],

        'adjective' => [
            'required' => [
                'positive_mf',
                'positive_n',
                'positive_pl'
            ],
            'optional' => [
                'comparative',
                'superlative'
            ]
        ],

        'determiner'       => ['required' => [], 'optional' => []],
        'numeral'          => ['required' => [], 'optional' => []],
        'adverb'           => ['required' => [], 'optional' => []],
        'preposition'      => ['required' => [], 'optional' => []],
        'conjunction'      => ['required' => [], 'optional' => []],
        'subjunction'      => ['required' => [], 'optional' => []],
        'infinitive_marker'=> ['required' => [], 'optional' => []],
        'interjection'     => ['required' => [], 'optional' => []],
        'pronoun'          => ['required' => [], 'optional' => []],
        'unknown'          => ['required' => [], 'optional' => []],
    ];

    public static function getGrammarSchema(string $wordClass): ?array
    {
        return self::GRAMMAR_SCHEMA[self::normalizeWordClass($wordClass)] ?? null;
    }

    public static function getRequiredFields(string $wordClass): array
    {
        return self::GRAMMAR_SCHEMA[self::normalizeWordClass($wordClass)]['required'] ?? [];
    }

    public static function getAllowedFields(string $wordClass): array
    {
        $schema = self::getGrammarSchema($wordClass);

        return array_values(array_unique(array_merge(
            $schema['required'] ?? [],
            $schema['optional'] ?? []
        )));
    }

    /* ==========================================================
       OUTPUT
    ========================================================== */

    public static function toPublic(array $entry, string $level = 'A2'): array
    {
        $wordClass = self::normalizeWordClass($entry['word_class'] ?? 'unknown');
        $subclass  = self::normalizeSubclass($wordClass, $entry['subclass'] ?? null);

        $out = [
            'found'      => true,
            'lemma'      => $entry['lemma'] ?? '',
            'language'   => $entry['language'] ?? 'nb',
            'word_class' => $wordClass,
            'word_class_label' => LexTerminology::label($wordClass)
        ];

        if ($subclass) {
            $out['subclass'] = $subclass;
        }

        $g = $entry['grammar'] ?? [];

        if ($g) {

            $forms = match ($wordClass) {

                'noun' => [
                    $g['indefinite_singular'] ?? '',
                    $g['definite_singular'] ?? '',
                    $g['indefinite_plural'] ?? '',
                    $g['definite_plural'] ?? ''
                ],

                'verb' => [
                    $g['infinitive'] ?? '',
                    $g['present'] ?? '',
                    $g['past'] ?? '',
                    $g['past_participle'] ?? ''
                ],

                'adjective' => [
                    $g['positive_mf'] ?? '',
                    $g['positive_n'] ?? '',
                    $g['positive_pl'] ?? ''
                ],

                default => array_values(array_filter($g))
            };

            $forms = array_filter($forms);

            if ($forms) {
                $out['grammar'] = [[
                    'label' => 'Bøyning',
                    'value' => implode(', ', $forms)
                ]];
            }
        }

        return $out;
    }
}