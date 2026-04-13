<?php
declare(strict_types=1);

final class LexTerminology
{
    private const MAP = [

        /* =========================
           Grunnstruktur
        ========================= */

        'lemma'        => 'Oppslagsord',
        'language'     => 'Språk',
        'word_class'   => 'Ordklasse',
        'grammar'      => 'Bøyning',
        'explanations' => 'Forklaringer',
        'level'        => 'Nivå',
        'explanation'  => 'Forklaring',
        'example'      => 'Eksempel',

        /* =========================
           Substantiv
        ========================= */

        'gender'              => 'Kjønn',
	'gender_alt'          => 'Kjønn (alt.)',
        'singular_indefinite' => 'Ubestemt entall',
        'singular_definite'   => 'Bestemt entall',
        'plural_indefinite'   => 'Ubestemt flertall',
        'plural_definite'     => 'Bestemt flertall',
	'singular_indefinite_alt'  => 'Entall ubestemt (alt.)',
	'singular_definite_alt'    => 'Entall bestemt (alt.)',
	'plural_indefinite_alt'    => 'Flertall ubestemt (alt.)',
	'plural_definite_alt'      => 'Flertall bestemt (alt.)',
        'countable'           => 'Tellbar',

        /* Kjønn – verdier */

        'm'  => 'Hankjønn',
        'f'  => 'Hunkjønn',
        'n'  => 'Intetkjønn',
        'pl' => 'Flertall',



        /* =========================
           Verb
        ========================= */

        'infinitive' => 'Infinitiv',
        'present'    => 'Presens',
        'past'       => 'Preteritum',
        'perfect'    => 'Presens perfektum',
        'passive'    => 'Passiv',
        'imperative' => 'Imperativ',

        /* =========================
           Adjektiv
        ========================= */

        'positive'             => 'Positiv',
        'neuter'               => 'Intetkjønn',
        'plural'               => 'Flertall',
        'definite'             => 'Bestemt form',
        'comparative'          => 'Komparativ',
        'superlative'          => 'Superlativ',
        'superlative_definite' => 'Bestemt superlativ',

        /* =========================
           Pronomen
        ========================= */

        'pron_personal'      => 'Personlig pronomen',
        'pron_possessive'    => 'Eiendomspronomen',
        'pron_reflexive'     => 'Refleksivt pronomen',
        'pron_demonstrative' => 'Demonstrativt pronomen',

        /* =========================
           Øvrige ordklasser
        ========================= */

        'substantiv'      => 'Substantiv',
        'verb'            => 'Verb',
        'adjektiv'        => 'Adjektiv',
        'adverb'          => 'Adverb',
        'preposisjon'     => 'Preposisjon',
        'konjunksjon'     => 'Konjunksjon',
        'subjunksjon'     => 'Subjunksjon',
        'infinitivsmerke' => 'Infinitivsmerke',
        'interjeksjon'    => 'Interjeksjon',
        'determinativ'    => 'Determinativ',
        'tallord'         => 'Tallord',
        'numeral'         => 'Tallord',

        /* =========================
           Meta
        ========================= */

        'verified'     => 'Redaktørverifisert',
        'not_verified' => 'Ikke verifisert',
        'ai_generated' => 'AI-generert',
        'manual'       => 'Manuelt opprettet',

        /* =========================
           UI
        ========================= */

        'edit_entry'         => 'Rediger oppslag',
        'all_entries'        => 'Alle oppslag',
        'control_panel'      => 'Kontrollpanel',
        'basic_info'         => 'Grunninformasjon',
        'save_and_verify'    => 'Lagre og verifiser',
        'back_to_list'       => 'Tilbake til liste',
        'status'             => 'Status',
        'edit'               => 'Rediger',
        'total'              => 'Totalt',
        'no_results'         => 'Ingen treff',
        'search_placeholder' => 'Søk oppslagsord...',
        'all_word_classes'   => 'Alle ordklasser',
        'filter'             => 'Filtrer',
    ];

    public static function label(string $key): string
    {
        if (isset(self::MAP[$key])) {
            return self::MAP[$key];
        }

        // Robust fallback (UI-vennlig)
        return ucfirst(str_replace('_', ' ', $key));
    }
}