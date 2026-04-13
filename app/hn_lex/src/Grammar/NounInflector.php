<?php
declare(strict_types=1);

namespace HnLex\Grammar;

final class NounInflector
{
    public function inflect(string $word): array
    {
        return [
            'indefinite_singular' => $word,
            'definite_singular'   => $word . 'en',
            'indefinite_plural'   => $word . 'er',
            'definite_plural'     => $word . 'ene'
        ];
    }
}