<?php

namespace HnBooks\Service;

use RuntimeException;
use DOMDocument;
use DOMXPath;

class TextGeneratorService
{
    public function generate(
        string $bookKey,
        string $textKey,
        string $title,
        string $rawHtml,
        ?string $level = null
    ): string {

        $bookKeyLower = strtolower(trim($bookKey));

        if (!preg_match('/-(\d{3})$/', $textKey, $m)) {
            throw new RuntimeException('Ugyldig text_key.');
        }

        $textNumber = (int)$m[1];

        $displayTitle = "Tekst {$textNumber}: " . trim($title);
        $pageTitle    = $displayTitle . " | Hverdagsnorsk";

        // ✅ RIKTIG PATH (via HN_ROOT)
        $baseDir = HN_ROOT . "/hn_books/books/{$bookKeyLower}/texts";

        if (!is_dir($baseDir)) {
            if (!mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
                throw new RuntimeException("Kunne ikke opprette mappe: $baseDir");
            }
        }

        $fileName = $textKey . '.html';
        $fullPath = $baseDir . '/' . $fileName;

        if (trim($rawHtml) === '') {
            throw new RuntimeException('Ingen tekst.');
        }

        // AUDIO
        $rawHtml = str_replace(
            '<span class="hn-audio-marker">[[LYD]]</span>',
            '[[LYD]]',
            $rawHtml
        );

        $audioPrefix  = strtoupper(substr($bookKeyLower, 0, 3));
        $audioCounter = 1;

        $rawHtml = preg_replace_callback(
            '/\[\[\s*LYD\s*\]\]/i',
            function () use ($audioPrefix, $textNumber, &$audioCounter) {
                $audioFile = "../audio/{$audioPrefix}-{$textNumber}-{$audioCounter}.m4a";
                $audioCounter++;

                return '<figure class="section-controls">
<audio controls preload="metadata">
<source src="' . htmlspecialchars($audioFile, ENT_QUOTES, 'UTF-8') . '" type="audio/mp4">
</audio>
</figure>';
            },
            $rawHtml
        );

        // SANITIZE
        $allowed = ['p','strong','em','ul','ol','li','h2','h3','blockquote','br','figure','audio','source','span','div'];

        libxml_use_internal_errors(true);

        $dom = new DOMDocument('1.0', 'UTF-8');

        $wrapped = '<html><body><div id="frag">'.$rawHtml.'</div></body></html>';
        $dom->loadHTML($wrapped);

        $frag = $dom->getElementById('frag');

        if (!$frag) {
            throw new RuntimeException('HTML parse feilet.');
        }

        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('.//*', $frag) as $node) {
            if (!in_array(strtolower($node->nodeName), $allowed)) {
                $node->parentNode?->removeChild($node);
            }
        }

        $html = '';
        foreach ($frag->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        // ✅ KORRIGERT TEMPLATE PATH
        $template = HN_ROOT . '/app/hn_admin/templates/text_template.php';

        if (!file_exists($template)) {
            throw new RuntimeException("Template finnes ikke: " . $template);
        }

        ob_start();
        require $template;
        $finalHtml = ob_get_clean();

        if (!$finalHtml) {
            throw new RuntimeException('Template tom.');
        }

        if (file_put_contents($fullPath, $finalHtml) === false) {
            throw new RuntimeException("Kunne ikke skrive fil: " . $fullPath);
        }

        return "/hn_books/books/{$bookKeyLower}/texts/{$textKey}.html";
    }
}