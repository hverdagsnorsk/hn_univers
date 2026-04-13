<?php
declare(strict_types=1);

final class TextExtractor
{

    public static function extractMainText(string $html): string
    {

        if ($html === '') {
            return '';
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument();

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        /*
        --------------------------------------------------
        Fjern elementer som ikke er tekst
        --------------------------------------------------
        */

        foreach ($xpath->query('//script|//style|//nav|//header|//footer') as $node) {
            $node->parentNode?->removeChild($node);
        }

        /*
        --------------------------------------------------
        HN Books tekstcontainer
        --------------------------------------------------
        */

        $nodes = $xpath->query("
            //div[contains(@class,'reader')]
            //p |
            //div[contains(@class,'reader')]
            //li |
            //div[contains(@class,'reader')]
            //h1 |
            //div[contains(@class,'reader')]
            //h2
        ");

        $text = '';

        if ($nodes && $nodes->length > 0) {

            foreach ($nodes as $node) {

                $line = trim((string)$node->textContent);

                if ($line === '') {
                    continue;
                }

                $text .= ' '.$line;

            }

        }

        /*
        --------------------------------------------------
        Fallback hvis reader ikke finnes
        --------------------------------------------------
        */

        if ($text === '') {

            $body = $xpath->query("//body")->item(0);

            if ($body) {
                $text = (string)$body->textContent;
            } else {
                $text = strip_tags($html);
            }

        }

        /*
        --------------------------------------------------
        Rens tekst
        --------------------------------------------------
        */

        $text = preg_replace('/\[\[LYD.*?\]\]/u','',$text);
        $text = preg_replace('/QR\s*kode.*?/iu','',$text);

        $text = preg_replace('/\s+/u',' ',$text ?? '');

        return trim($text ?? '');

    }


    /*
    --------------------------------------------------
    Legacy wrapper (bakoverkompatibilitet)
    --------------------------------------------------
    */

    public static function extract(string $html): string
    {
        return self::extractMainText($html);
    }

}