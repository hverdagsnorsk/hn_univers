<?php
declare(strict_types=1);

final class TaskDifficulty
{
    public static function detectLevel(string $text): string
    {
        $words = str_word_count($text);

        if ($words < 120) return "A1";

        if ($words < 220) return "A2";

        if ($words < 350) return "B1";

        return "B2";
    }
}