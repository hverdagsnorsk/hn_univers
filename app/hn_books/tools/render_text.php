<?php
declare(strict_types=1);

/* ============================================================
   render_text.php – FULL BEVARING + AUDIO-LOGIKK
============================================================ */

$book = $_GET['book'] ?? '';
$file = $_GET['file'] ?? '';

if (!preg_match('/^[a-z0-9_-]+$/i', $book)) {
  http_response_code(400); exit('Ugyldig bok');
}
if (!preg_match('/^[a-z0-9_-]+$/i', $file)) {
  http_response_code(400); exit('Ugyldig fil');
}

/* -------------------------------------------------- */
$toolsDir  = __DIR__;
$booksRoot = realpath($toolsDir . '/../');
$booksDir  = $booksRoot . '/books';

$templateFile = realpath($toolsDir . '/../../hn_admin/templates/text_template.php');
if (!$templateFile) {
  http_response_code(500); exit('Template mangler');
}

/* -------------------------------------------------- */
$bookDir = null;
foreach (scandir($booksDir) as $dir) {
  if ($dir === '.' || $dir === '..') continue;
  if (is_dir("$booksDir/$dir") && strcasecmp($dir, $book) === 0) {
    $bookDir = "$booksDir/$dir";
    break;
  }
}
if (!$bookDir) {
  http_response_code(404); exit('Bok finnes ikke');
}

$textFile = "$bookDir/texts/$file.html";
if (!file_exists($textFile)) {
  http_response_code(404); exit('Tekst finnes ikke');
}

copy($textFile, $textFile . '.bak');

/* ============================================================
   1. LES HTML
============================================================ */
$oldHtml = file_get_contents($textFile);

/* ============================================================
   2. GENERER AUDIO FRA [[LYD]]
============================================================ */

$prefix = strtoupper(substr($book, 0, 3));

preg_match('/(\d+)/', $file, $m);
$chapter = isset($m[1]) ? (int)$m[1] : 1;

$counter = 1;

$oldHtml = preg_replace_callback('/\[\[LYD\]\]/', function () use ($prefix, $chapter, &$counter) {

    $src = "../audio/{$prefix}-{$chapter}-{$counter}.m4a";
    $counter++;

    return '
<figure class="section-controls">
  <audio controls preload="metadata">
    <source src="' . $src . '" type="audio/mp4">
  </audio>
</figure>';

}, $oldHtml);

/* ============================================================
   3. DOM LOAD
============================================================ */
libxml_use_internal_errors(true);
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->loadHTML($oldHtml, LIBXML_NOERROR | LIBXML_NOWARNING);
$xpath = new DOMXPath($dom);

/* ============================================================
   4. TITTEL
============================================================ */
$titleNode =
  $xpath->query('//h2')->item(0)
  ?? $xpath->query('//h1')->item(0);

$displayTitle = $titleNode
  ? trim(preg_replace('/^Tekst\s+\d+\s*:\s*/i', '', $titleNode->textContent))
  : $file;

$pageTitle = $displayTitle . ' | Hverdagsnorsk';

/* ============================================================
   5. BODY INNHOLD
   👉 BEVAR ALT (IKKE filtrer bort HTML)
============================================================ */
$body = $dom->getElementsByTagName('body')->item(0);

$contentHtml = '';

if ($body) {
    foreach ($body->childNodes as $node) {

        // ignorer tomme tekstnoder
        if ($node->nodeType === XML_TEXT_NODE && trim($node->textContent) === '') {
            continue;
        }

        // ignorer footer-tekst
        if (
            $node->nodeName === 'p' &&
            preg_match('/©|personvern|kontakt/i', $node->textContent)
        ) {
            continue;
        }

        $contentHtml .= $dom->saveHTML($node);
    }
}

/* ============================================================
   6. AUDIO-VALIDERING
   👉 Fjern audio som ikke har tekst etter seg
============================================================ */

libxml_use_internal_errors(true);
$dom2 = new DOMDocument('1.0', 'UTF-8');
$dom2->loadHTML($contentHtml, LIBXML_NOERROR | LIBXML_NOWARNING);

$xpath2 = new DOMXPath($dom2);

$figures = $xpath2->query('//figure[audio]');

foreach ($figures as $figure) {

    $next = $figure->nextSibling;
    $hasTextAfter = false;

    while ($next) {

        if ($next->nodeType === XML_TEXT_NODE && trim($next->textContent) === '') {
            $next = $next->nextSibling;
            continue;
        }

        if ($next->nodeName === 'p' && trim($next->textContent) !== '') {
            $hasTextAfter = true;
        }

        break;
    }

    if (!$hasTextAfter) {
        $figure->parentNode->removeChild($figure);
    }
}

$contentHtml = '';
$body2 = $dom2->getElementsByTagName('body')->item(0);

if ($body2) {
    foreach ($body2->childNodes as $node) {
        $contentHtml .= $dom2->saveHTML($node);
    }
}

/* ============================================================
   7. TEMPLATE
============================================================ */
$bookKeyLower = basename($bookDir);
$assetVersion = filemtime($textFile);

ob_start();
require $templateFile;
$newHtml = ob_get_clean();

if (!$newHtml || stripos($newHtml, '<html') === false) {
  http_response_code(500);
  exit('Rendering feilet');
}

/* ============================================================
   8. SKRIV FIL
============================================================ */
file_put_contents($textFile, $newHtml);

header('Content-Type: text/plain; charset=utf-8');
echo "OK – full HTML bevart + audio generert:\n$textFile\nBackup: {$textFile}.bak";