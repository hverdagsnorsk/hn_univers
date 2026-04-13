<?php
declare(strict_types=1);

/*
 |--------------------------------------------------
 | Enkel lemma-normalisering (før AI)
 |--------------------------------------------------
 | Brukes til å redusere unødige AI-kall
 */

function normalize_lemma(string $word): string
{
    $w = mb_strtolower($word, 'UTF-8');

    // Enkle regler først
    $rules = [
        // verb – svært vanlige
        '/^(gikk|går|gått)$/u' => 'gå',
        '/^(er|var|vært)$/u'   => 'være',
        '/^(har|hadde)$/u'    => 'ha',

        // adjektiv
        '/ere$/u' => '',
        '/est$/u' => '',

        // substantiv – bestemt/flertall
        '/ene$/u' => '',
        '/er$/u'  => '',
        '/en$/u'  => '',
        '/et$/u'  => '',
        '/a$/u'   => '',
    ];

    foreach ($rules as $pattern => $lemma) {
        if (preg_match($pattern, $w)) {
            return $lemma !== '' ? $lemma : preg_replace($pattern, '', $w);
        }
    }

    return $w;
}
