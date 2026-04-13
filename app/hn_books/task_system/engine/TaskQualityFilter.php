<?php
declare(strict_types=1);

final class TaskQualityFilter
{

    public static function filter(array $tasks): array
    {

        $out = [];
        $seen = [];

        foreach ($tasks as $task) {

            $type = $task["type"] ?? "unknown";

            /*
            --------------------------------------------------
            WRITING TASKS
            --------------------------------------------------
            */

            if ($type === "writing") {

                if (empty($task["task"])) {
                    continue;
                }

                $out[] = $task;
                continue;

            }

            /*
            --------------------------------------------------
            SENTENCE CHECK
            --------------------------------------------------
            */

            $sentence = trim($task["sentence"] ?? "");

            if ($sentence === '') {
                continue;
            }

            if (mb_strlen($sentence) < 25) {
                continue;
            }

            /*
            --------------------------------------------------
            DUPLICATE CHECK
            --------------------------------------------------
            */

            $norm = mb_strtolower(preg_replace('/\s+/u',' ',$sentence));

            if (isset($seen[$norm])) {
                continue;
            }

            $seen[$norm] = true;

            /*
            --------------------------------------------------
            MULTIPLE CHOICE
            --------------------------------------------------
            */

            if ($type === "multiple_choice") {

                if (empty($task["options"]) || count($task["options"]) < 3) {
                    continue;
                }

                $unique = array_unique($task["options"]);

                if (count($unique) < 3) {
                    continue;
                }

            }

            /*
            --------------------------------------------------
            FILL TASK
            --------------------------------------------------
            */

            if ($type === "fill") {

                if (empty($task["answer"])) {
                    continue;
                }

                if (!str_contains($sentence,"_____")) {
                    continue;
                }

            }

            /*
            --------------------------------------------------
            WORD ORDER
            --------------------------------------------------
            */

            if ($type === "word_order") {

                if (empty($task["scrambled"]) || empty($task["answer"])) {
                    continue;
                }

            }

            /*
            --------------------------------------------------
            PREPOSITION
            --------------------------------------------------
            */

            if ($type === "preposition") {

                if (empty($task["answer"])) {
                    continue;
                }

            }

            /*
            --------------------------------------------------
            VERB / NOUN INFLECTION
            --------------------------------------------------
            */

            if ($type === "verb_inflection" || $type === "noun_inflection") {

                if (empty($task["lemma"])) {
                    continue;
                }

                if (empty($task["forms"])) {
                    continue;
                }

            }

            /*
            --------------------------------------------------
            SYNTAX
            --------------------------------------------------
            */

            if ($type === "subjunction") {

                if (empty($task["question"])) {
                    continue;
                }

            }

            $out[] = $task;

        }

        return $out;

    }

}