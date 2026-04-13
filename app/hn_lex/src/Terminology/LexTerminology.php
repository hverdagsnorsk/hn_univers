<?php
declare(strict_types=1);

namespace HnLex\Terminology;

use HnLex\Contracts\LexContract;

final class LexTerminology
{
    /* ==========================================================
       LABELS (UI)
    ========================================================== */

    private const LABELS = [

        'noun' => 'Substantiv',
        'verb' => 'Verb',
        'adjective' => 'Adjektiv',
        'adverb' => 'Adverb',
        'pronoun' => 'Pronomen',
        'determiner' => 'Determinativ',
        'numeral' => 'Tallord',
        'preposition' => 'Preposisjon',
        'conjunction' => 'Konjunksjon',
        'subjunction' => 'Subjunksjon',
        'infinitive_marker' => 'Infinitivsmerke',
        'interjection' => 'Interjeksjon',
        'unknown' => 'Ukjent'
    ];

    /* ==========================================================
       🔥 SUBCLASS LABELS (NY)
    ========================================================== */

    private const SUBCLASS_LABELS = [

        'pronoun' => [
            'personal' => 'personlig pronomen',
            'possessive' => 'eiendomspronomen',
            'demonstrative' => 'påpekende pronomen',
            'reflexive' => 'refleksivt pronomen',
            'interrogative' => 'spørrepronomen',
            'relative' => 'relativt pronomen',
            'indefinite' => 'ubestemt pronomen'
        ],

        'determiner' => [
            'article' => 'artikkel',
            'possessive' => 'eiendomsdeterminativ',
            'demonstrative' => 'påpekende determinativ',
            'quantifier' => 'mengdeord'
        ]
    ];

    /* ==========================================================
       SHORT LABELS
    ========================================================== */

    private const SHORT_LABELS = [

        'noun' => 'subst.',
        'verb' => 'verb',
        'adjective' => 'adj.',
        'adverb' => 'adv.',
        'pronoun' => 'pron.',
        'determiner' => 'det.',
        'numeral' => 'num.',
        'preposition' => 'prep.',
        'conjunction' => 'konj.',
        'subjunction' => 'subj.',
        'infinitive_marker' => 'inf.m.',
        'interjection' => 'interj.',
        'unknown' => '?'
    ];

    /* ==========================================================
       STANDARD LABEL
    ========================================================== */

    public static function label(string $wordClass): string
    {
        $normalized = LexContract::normalizeWordClass($wordClass);
        return self::LABELS[$normalized] ?? 'Ukjent';
    }

    /* ==========================================================
       🔥 LABEL MED SUBCLASS (NY – VIKTIG)
    ========================================================== */

    public static function labelWithSubclass(
        string $wordClass,
        ?string $subclass = null
    ): string {

        $wordClass = LexContract::normalizeWordClass($wordClass);
        $subclass  = LexContract::normalizeSubclass($wordClass, $subclass);

        $base = self::label($wordClass);

        if (!$subclass) {
            return $base;
        }

        $subLabel = self::SUBCLASS_LABELS[$wordClass][$subclass] ?? null;

        if (!$subLabel) {
            return $base;
        }

        return "{$base} ({$subLabel})";
    }

    /* ==========================================================
       SHORT LABEL
    ========================================================== */

    public static function short(string $wordClass): string
    {
        $normalized = LexContract::normalizeWordClass($wordClass);
        return self::SHORT_LABELS[$normalized] ?? '?';
    }

    /* ==========================================================
       DEBUG
    ========================================================== */

    public static function debug(string $wordClass): string
    {
        $normalized = LexContract::normalizeWordClass($wordClass);

        return sprintf(
            '%s (%s)',
            self::label($normalized),
            $normalized
        );
    }

    /* ==========================================================
       VALIDATION
    ========================================================== */

    public static function isKnown(string $wordClass): bool
    {
        $normalized = LexContract::normalizeWordClass($wordClass);
        return $normalized !== 'unknown';
    }

    /* ==========================================================
       ALL
    ========================================================== */

    public static function all(): array
    {
        return self::LABELS;
    }
}