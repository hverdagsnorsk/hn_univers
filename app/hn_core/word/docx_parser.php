<?php
declare(strict_types=1);

use PhpOffice\PhpWord\IOFactory;

/**
 * Leser .docx og returnerer blokker
 * Felles Word-parser for HN-universet
 */
function parse_docx_to_blocks(string $filePath): array
{
    $blocks = [];

    $phpWord = IOFactory::load($filePath);

    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $el) {

            if (method_exists($el, 'getText')) {
                $text = trim($el->getText());
                if ($text !== '') {
                    $blocks[] = [
                        'type' => 'p',
                        'content' => $text
                    ];
                }
            }

            if ($el instanceof \PhpOffice\PhpWord\Element\ListItem) {
                $text = trim($el->getText());
                if ($text !== '') {
                    $blocks[] = [
                        'type' => 'li',
                        'content' => $text
                    ];
                }
            }
        }
    }

    return $blocks;
}
