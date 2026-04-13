<?php
declare(strict_types=1);

namespace HnLex\Grammar;

final class IrregularRepository
{
    public static function adjectives(): array
    {
        return [
            'liten' => [
                'positive_mf' => 'liten',
                'positive_n'  => 'lite',
                'positive_pl' => 'små'
            ],
            'god' => [
                'positive_mf' => 'god',
                'positive_n'  => 'godt',
                'positive_pl' => 'gode'
            ]
        ];
    }

    public static function verbs(): array
    {
        return [
            'være' => [
                'infinitive' => 'være',
                'present' => 'er',
                'past' => 'var',
                'past_participle' => 'vært'
            ]
        ];
    }
}