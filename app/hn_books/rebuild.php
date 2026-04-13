<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| rebuild.php
|--------------------------------------------------------------------------
| TRYGG rebuild av ÉN eksisterende tekst-HTML
| - Endrer kun layout / markup
| - Bevarer eksisterende audio-filer og rekkefølge
| - Ingen bruk av generate_text_html()
|
| Bruk:
|   /hn_books/rebuild.php?id=4
|--------------------------------------------------------------------------
*/

// RIKTIG bootstrap for hn_books
require_once __DIR__ . '/engine/bootstrap.php';

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$textId = (int)($_GET['id'] ?? 0);
if ($textId <= 0) {
    http_response_code(400);
    exit('Mangler eller ugyldig ?id=');
}

/* --------------------------------------------------
   Hent tekst fra DB
-------------------------------------------------- */
$stmt = $pdo->prepare("
    SELECT id, book_key, text_key, title, source_path
    FROM texts
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $textId]);
$text = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$text) {
    http_response_code(404);
    exit('Fant ikke tekst i databasen.');
}

/* --------------------------------------------------
   Finn HTML-fil
-------------------------------------------------- */
$htmlPath = $_SERVER['DOCUMENT_ROOT'] . $text['source_path'];

if (!is_file($htmlPath)) {
    http_response_code(404);
    exit('Fant ikke HTML-fil: ' . $htmlPath);
}

$html = file_get_contents($htmlPath);
if ($html === false) {
    http_response_code(500);
    exit('Kunne ikke lese HTML-fil.');
}

/* --------------------------------------------------
   BACKUP (alltid)
-------------------------------------------------- */
$backupPath = $htmlPath . '.bak';
if (!is_file($backupPath)) {
    copy($htmlPath, $backupPath);
}

/* --------------------------------------------------
   EKSEMPEL-ENDRING:
   Flytt audio ut av <figure> og inn i egen blokk
-------------------------------------------------- */
$html = preg_replace(
    '#<figure[^>]*class=["\']section-controls["\'][^>]*>.*?(<audio[^>]*>.*?</audio>).*?</figure>#si',
    '<div class="audio-block">$1</div>',
    $html
);
/* --------------------------------------------------
   RYDDING:
   Fjern dobbel "Tekst N: Tekst N:" hvis den finnes
-------------------------------------------------- */
$html = preg_replace(
    '/(Tekst\s+\d+:\s*)\1/iu',
    '$1',
    $html
);

/* --------------------------------------------------
   Skriv ny HTML
-------------------------------------------------- */
if (file_put_contents($htmlPath, $html) === false) {
    http_response_code(500);
    exit('Kunne ikke skrive oppdatert HTML.');
}

/* --------------------------------------------------
   Ferdig
-------------------------------------------------- */
header('Content-Type: text/plain; charset=UTF-8');

echo "OK – rebuild fullført\n";
echo "Tekst ID: {$textId}\n";
echo "Fil: {$text['source_path']}\n";
echo "Backup: {$backupPath}\n";
