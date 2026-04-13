<?php

class NorwegianGrammarValidator
{

    public static function validateNoun(array $g): array
    {

        $lemma = $g['lemma'] ?? null;

        if (!$lemma) {
            return $g;
        }

        /* zero plural nouns */

        $zeroPlural = [
            'barn','dyr','arbeid','liv','spørsmål','svar','ting'
        ];

        if (in_array($lemma,$zeroPlural,true)) {

            $g['sg_indef'] = $lemma;
            $g['sg_def']   = $lemma.'et';
            $g['pl_indef'] = $lemma;
            $g['pl_def']   = $lemma.'ene';

            return $g;
        }

        /* safety fix */

        if (isset($g['pl_indef']) && str_ends_with($g['pl_indef'],'er')) {

            if (!str_ends_with($lemma,'er')) {

                /* common AI hallucination */

                $g['pl_indef'] = $lemma;
            }
        }

        return $g;
    }

}