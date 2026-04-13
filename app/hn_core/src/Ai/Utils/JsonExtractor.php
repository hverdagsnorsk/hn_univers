<?php
declare(strict_types=1);

namespace HnCore\Ai\Utils;

class JsonExtractor
{
    public static function extract(string $text): array
    {
        if (preg_match('/\{.*\}/s',$text,$m)) {
            return json_decode($m[0],true) ?? [];
        }

        throw new \RuntimeException('AI did not return JSON');
    }
}