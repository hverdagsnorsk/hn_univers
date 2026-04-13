<?php
declare(strict_types=1);

namespace HnCore\Utils;

final class Html
{
    public static function e(?string $value): string
    {
        return htmlspecialchars(
            $value ?? '',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}