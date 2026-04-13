<?php
declare(strict_types=1);

namespace HnLex\Grammar;

final class VerbInflector
{
    public function inflect(string $word): array
    {
        $word = mb_strtolower($word);

        $irregular = IrregularRepository::verbs();

        if (isset($irregular[$word])) {
            return $irregular[$word];
        }

        return [
            'infinitive' => $word,
            'present' => $word . 'r',
            'past' => $word . 'te',
            'past_participle' => $word . 't'
        ];
    }
}