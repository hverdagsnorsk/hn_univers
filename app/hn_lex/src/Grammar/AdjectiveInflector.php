<?php
declare(strict_types=1);

namespace HnLex\Grammar;

final class AdjectiveInflector
{
    public function inflect(string $word): array
    {
        $word = mb_strtolower($word);

        $irregular = IrregularRepository::adjectives();

        if (isset($irregular[$word])) {
            return $irregular[$word];
        }

        return [
            'positive_mf' => $word,
            'positive_n'  => $this->toNeuter($word),
            'positive_pl' => $this->toPlural($word),
        ];
    }

    private function toNeuter(string $word): string
    {
        if (str_ends_with($word, 't')) {
            return $word;
        }

        return $word . 't';
    }

    private function toPlural(string $word): string
    {
        if (str_ends_with($word, 'e')) {
            return $word;
        }

        return $word . 'e';
    }
}