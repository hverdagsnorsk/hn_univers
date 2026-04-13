<?php
declare(strict_types=1);

final class SyntaxAnalyzer
{

    private array $subjunctions = [
        "fordi",
        "at",
        "hvis",
        "når",
        "mens",
        "da",
        "dersom",
        "siden",
        "selv om",
        "selv om",
        "før",
        "etter at",
        "så lenge"
    ];


    private array $sentenceAdverbials = [
        "i dag",
        "i morgen",
        "nå",
        "da",
        "før",
        "etter",
        "senere",
        "deretter",
        "plutselig",
        "heldigvis",
        "dessverre"
    ];


    /*
    --------------------------------------------------
    DETECT SUBORDINATE CLAUSE
    --------------------------------------------------
    */

    public function detectSubordinate(string $sentence): ?string
    {

        $sentence = mb_strtolower($sentence);

        foreach ($this->subjunctions as $s) {

            $pattern = '/\b'.preg_quote($s,'/').'\b/u';

            if (preg_match($pattern,$sentence)) {
                return $s;
            }

        }

        return null;

    }


    /*
    --------------------------------------------------
    SENTENCE STARTS WITH ADVERBIAL
    --------------------------------------------------
    */

    public function startsWithAdverbial(string $sentence): bool
    {

        $sentence = mb_strtolower(trim($sentence));

        foreach ($this->sentenceAdverbials as $adv) {

            if (str_starts_with($sentence,$adv." ")) {
                return true;
            }

        }

        return false;

    }


    /*
    --------------------------------------------------
    DETECT VERB (enkel heuristikk)
    --------------------------------------------------
    */

    public function hasVerb(string $sentence): bool
    {

        return preg_match(
            '/\b(er|var|har|hadde|skal|må|kan|vil|blir|ble)\b/u',
            mb_strtolower($sentence)
        ) === 1;

    }


    /*
    --------------------------------------------------
    DETECT V2 STRUCTURE
    --------------------------------------------------
    */

    public function looksLikeV2(string $sentence): bool
    {

        $words = preg_split('/\s+/u',trim($sentence));

        if(count($words) < 3){
            return false;
        }

        $verbCandidates = [
            "er","var","har","hadde",
            "skal","må","kan","vil",
            "blir","ble"
        ];

        $second = mb_strtolower($words[1]);

        return in_array($second,$verbCandidates,true);

    }


    /*
    --------------------------------------------------
    SIMPLE CLAUSE TYPE DETECTION
    --------------------------------------------------
    */

    public function clauseType(string $sentence): string
    {

        if ($this->detectSubordinate($sentence)) {
            return "subordinate";
        }

        if ($this->startsWithAdverbial($sentence)) {
            return "adverbial_first";
        }

        return "main";

    }

}