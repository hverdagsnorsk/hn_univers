<?php
declare(strict_types=1);

final class LinguisticTaskGenerator
{

    /*
    --------------------------------------------------
    GENERATE VERB TASK FROM SENTENCE
    --------------------------------------------------
    */

    public static function generateVerbTask(string $sentence,array $forms): ?array
    {

        if(empty($forms["present"]) || empty($forms["past"])) {
            return null;
        }

        $verbForms = [

            $forms["present"],
            $forms["past"],
            $forms["infinitive"] ?? $forms["present"]

        ];

        $correct = $forms["past"];

        if(!str_contains($sentence,$correct)){
            return null;
        }

        $questionSentence = str_replace(
            $correct,
            "_____",
            $sentence
        );

        $options = array_unique($verbForms);

        shuffle($options);

        $correctIndex = array_search($correct,$options,true);

        return [

            "type"=>"verb_inflection",

            "question"=>"Velg riktig verbform",

            "sentence"=>$questionSentence,

            "options"=>$options,

            "correct"=>$correctIndex

        ];

    }


    /*
    --------------------------------------------------
    GENERATE NOUN TASK FROM SENTENCE
    --------------------------------------------------
    */

    public static function generateNounTask(string $sentence,array $forms): ?array
    {

        if(empty($forms["singular_indefinite"]) || empty($forms["singular_definite"])) {
            return null;
        }

        $target = $forms["singular_definite"];

        if(!str_contains($sentence,$target)){
            return null;
        }

        $questionSentence = str_replace(
            $target,
            "_____",
            $sentence
        );

        $options = [

            $forms["singular_indefinite"],
            $forms["singular_definite"],
            $forms["plural_indefinite"] ?? $forms["singular_indefinite"]

        ];

        $options = array_unique(array_filter($options));

        shuffle($options);

        $correctIndex = array_search($target,$options,true);

        return [

            "type"=>"noun_inflection",

            "question"=>"Velg riktig substantivform",

            "sentence"=>$questionSentence,

            "options"=>$options,

            "correct"=>$correctIndex

        ];

    }


    /*
    --------------------------------------------------
    PREPOSITION TASK FROM SENTENCE
    --------------------------------------------------
    */

    public static function generatePrepositionTask(string $sentence): ?array
    {

        $preps = ["i","på","til","fra","med","over","under"];

        foreach($preps as $prep){

            if(str_contains(" ".$sentence." "," ".$prep." ")){

                $questionSentence = str_replace(
                    " ".$prep." ",
                    " ___ ",
                    $sentence
                );

                $options = $preps;

                shuffle($options);

                $correct = array_search($prep,$options,true);

                return [

                    "type"=>"preposition",

                    "question"=>"Velg riktig preposisjon",

                    "sentence"=>$questionSentence,

                    "options"=>$options,

                    "correct"=>$correct

                ];

            }

        }

        return null;

    }


    /*
    --------------------------------------------------
    ADJECTIVE COMPARISON
    --------------------------------------------------
    */

    public static function generateAdjectiveTask(array $forms): ?array
    {

        if(empty($forms["positive"]) || empty($forms["comparative"])) {
            return null;
        }

        $sentence = "Denne maskinen er ____ enn den andre";

        $options = [

            $forms["positive"],
            $forms["comparative"],
            $forms["superlative"] ?? $forms["positive"]

        ];

        $options = array_unique(array_filter($options));

        shuffle($options);

        $correctIndex = array_search(
            $forms["comparative"],
            $options,
            true
        );

        return [

            "type"=>"adjective_comparison",

            "question"=>"Velg riktig grad av adjektivet",

            "sentence"=>$sentence,

            "options"=>$options,

            "correct"=>$correctIndex

        ];

    }


    /*
    --------------------------------------------------
    WORD ORDER TASK
    --------------------------------------------------
    */

    public static function generateWordOrderTask(string $sentence): array
    {

        $words = preg_split('/\s+/u',trim($sentence));

        $scrambled = $words;

        shuffle($scrambled);

        return [

            "type"=>"word_order",

            "question"=>"Sett ordene i riktig rekkefølge",

            "scrambled"=>implode(" ",$scrambled),

            "answer"=>$sentence

        ];

    }


    /*
    --------------------------------------------------
    SYNTAX TASK (SUBJUNCTION)
    --------------------------------------------------
    */

    public static function generateSubjunctionTask(string $sentence,string $sub): array
    {

        return [

            "type"=>"subjunction",

            "question"=>"Hvilken subjunksjon brukes?",

            "sentence"=>$sentence,

            "answer"=>$sub

        ];

    }

}