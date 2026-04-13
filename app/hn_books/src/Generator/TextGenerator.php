<?php
declare(strict_types=1);

namespace HnBooks\Generator;

final class TextGenerator
{
    public static function generate(
        string $bookKey,
        string $textKey,
        string $title,
        string $rawHtml,
        ?string $level = null
    ): string {

        $baseDir = HN_ROOT . '/app/hn_books/books/' . $bookKey . '/texts';

        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
                throw new \RuntimeException('Kunne ikke opprette mappe: ' . $baseDir);
            }
        }

        $fileName = $textKey . '.html';
        $filePath = $baseDir . '/' . $fileName;

        $templatePath = HN_ROOT . '/app/hn_books/templates/text_template.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('Template mangler: ' . $templatePath);
        }

        // 🔴 KRITISK: normaliser spacing HELT TIL SLUTT
        $contentHtml = self::normalizeFinalSpacing($rawHtml);

        $pageTitle    = $title . ' | Hverdagsnorsk';
        $displayTitle = $title;

        // cache-busting
        $assetVersion = time();

        ob_start();
        require $templatePath;
        $html = ob_get_clean();

        if ($html === false) {
            throw new \RuntimeException('Output buffering feilet');
        }

        if (file_put_contents($filePath, $html) === false) {
            throw new \RuntimeException('Kunne ikke skrive fil: ' . $filePath);
        }

        return '/hn_books/books/' . $bookKey . '/texts/' . $fileName;
    }

    /**
     * 🔧 Norsk typografi-fix
     * - fjerner mellomrom før tegn
     * - håndterer &nbsp;
     * - bevarer HTML
     */
 private static function normalizeFinalSpacing(string $html): string
{
    libxml_use_internal_errors(true);

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

    $xpath = new \DOMXPath($dom);

    foreach ($xpath->query('//text()') as $textNode) {

        $text = $textNode->nodeValue;

        // NBSP → space
        $text = str_replace("\xC2\xA0", ' ', $text);
        $text = str_replace("\u{00A0}", ' ', $text);

        // typografi-fix
        $text = preg_replace('/\s+([.,;:!?])/u', '$1', $text);
        $text = preg_replace('/\s+([)\]\}»”])/u', '$1', $text);
        $text = preg_replace('/([(\[\{«“])\s+/u', '$1', $text);

        // rydde dobbel space
        $text = preg_replace('/[ \t]{2,}/u', ' ', $text);

        $textNode->nodeValue = $text;
    }

    // hent body innhold
    $body = $dom->getElementsByTagName('body')->item(0);

    $cleanHtml = '';

    if ($body) {
        foreach ($body->childNodes as $node) {
            $cleanHtml .= $dom->saveHTML($node);
        }
    }

    return $cleanHtml;
}