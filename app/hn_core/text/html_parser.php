<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HTML / tekst → stabile blokker
|--------------------------------------------------------------------------
| Felles tekst-parser for HN-universet
|--------------------------------------------------------------------------
*/

function parse_html_to_blocks(string $input): array
{
    $input = trim($input);
    if ($input === '') {
        return [];
    }

    $input = str_replace(["\r\n", "\r"], "\n", $input);

    $input = preg_replace('/<br\s*\/?>/i', "\n", $input);
    $input = preg_replace('/<\/p>/i', "\n\n", $input);
    $input = strip_tags($input);

    $lines = preg_split("/\n+/", $input);

    $blocks = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^\d+(\.\d+)*\s+[A-ZÆØÅ]/u', $line)) {
            $blocks[] = ['type' => 'h', 'content' => $line];
            continue;
        }

        if (preg_match('/^[\-\*\•]\s+/u', $line)) {
            $blocks[] = [
                'type' => 'li',
                'content' => preg_replace('/^[\-\*\•]\s+/u', '', $line)
            ];
            continue;
        }

        if (preg_match('/^\d+(\.\d+)*[\)\.]?\s+/u', $line)) {
            $blocks[] = ['type' => 'li', 'content' => $line];
            continue;
        }

        $blocks[] = ['type' => 'p', 'content' => $line];
    }

    return $blocks;
}
