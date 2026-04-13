<?php
declare(strict_types=1);

function word_to_clean_html(string $docxPath): string
{
    $tmpHtml = tempnam(sys_get_temp_dir(), 'word_') . '.html';

    // Pandoc – samme som hn_translate
    $cmd = sprintf(
        'pandoc %s -f docx -t html --standalone -o %s',
        escapeshellarg($docxPath),
        escapeshellarg($tmpHtml)
    );

    exec($cmd, $out, $code);

    if ($code !== 0 || !is_file($tmpHtml)) {
        throw new RuntimeException('Pandoc-konvertering feilet');
    }

    $html = file_get_contents($tmpHtml);
    unlink($tmpHtml);

    // 🔹 RENSING (samme stil som hn_translate)
    $html = preg_replace('/<!--\[if.*?\]-->|<!--\[endif\]-->/is', '', $html);
    $html = preg_replace('/<\/?(o|w|m):[^>]+>/i', '', $html);
    $html = preg_replace('/\s+(class|style|lang|width|height)="[^"]*"/i', '', $html);

    // body-only
    if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
        $html = $m[1];
    }

    return trim($html);
}
