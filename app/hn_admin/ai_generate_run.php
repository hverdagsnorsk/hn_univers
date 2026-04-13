<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| hn_admin/ai_generate_run.php
| Kontrollert AI-generering per oppgavetype (steg 2)
|------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/ai/openai_client.php';

header('Content-Type: application/json; charset=utf-8');

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$textId = (int)($_POST['text_id'] ?? 0);
$mode   = (string)($_POST['mode'] ?? '');

$allowedModes = ['mcq', 'short', 'fill', 'match', 'writing'];

if ($textId <= 0) {
    echo json_encode(['error' => 'Ugyldig tekst-ID']);
    exit;
}

if (!in_array($mode, $allowedModes, true)) {
    echo json_encode(['error' => 'Ugyldig mode']);
    exit;
}

/* --------------------------------------------------
   Hent tekstfil
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT title, source_path
    FROM texts
    WHERE id = :id
    LIMIT 1
");
$stmt->execute(['id' => $textId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['source_path'])) {
    echo json_encode(['error' => 'Tekstfil ikke definert']);
    exit;
}

$file = $_SERVER['DOCUMENT_ROOT'] . $row['source_path'];

if (!is_readable($file)) {
    echo json_encode(['error' => 'Tekstfil ikke funnet på disk']);
    exit;
}

/* --------------------------------------------------
   Les og rens HTML
-------------------------------------------------- */
libxml_use_internal_errors(true);

$html = file_get_contents($file);
if ($html === false) {
    echo json_encode(['error' => 'Kunne ikke lese tekstfil']);
    exit;
}

$dom = new DOMDocument('1.0', 'UTF-8');
$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);

$xpath = new DOMXPath($dom);
$nodes = $xpath->query('//h2 | //p');

$textParts = [];
foreach ($nodes as $node) {
    $content = trim($node->textContent);
    if ($content !== '') {
        $textParts[] = $content;
    }
}

$cleanText = implode("\n\n", $textParts);

if ($cleanText === '') {
    echo json_encode(['error' => 'Ingen lesbar tekst funnet']);
    exit;
}

/* --------------------------------------------------
   Prompt per mode
-------------------------------------------------- */
$level = 'A2/B1';

function baseHeader(string $level): string {
    return <<<TXT
Du er en faglig presis lærer i norsk som andrespråk.
Nivå: {$level}

DU MÅ RETURNERE KUN gyldig JSON (ET array).
Ingen forklaringer. Ingen markdown.
Ikke pakk inn i objekt. Kun: [ ... ].

TXT;
}

switch ($mode) {

    case 'mcq':
        $prompt = baseHeader($level) . <<<PROMPT
Lag NØYAKTIG 6 flervalgsoppgaver (MCQ) basert på teksten.

KRAV:
- task_type = "mcq"
- 4 svaralternativer
- Én korrekt løsning

STRUKTUR:
{
  "task_type": "mcq",
  "payload": {
    "prompt": "Spørsmål",
    "choices": ["A", "B", "C", "D"],
    "correct_index": 0
  }
}

TEKST:
{$cleanText}
PROMPT;
        break;

    case 'short':
        $prompt = baseHeader($level) . <<<PROMPT
Lag NØYAKTIG 5 åpne leseforståelsesspørsmål.

KRAV:
- task_type = "short"
- Krever korte frie svar
- Ikke ja/nei

STRUKTUR:
{
  "task_type": "short",
  "payload": {
    "prompt": "Spørsmål"
  }
}

TEKST:
{$cleanText}
PROMPT;
        break;

    case 'fill':
        $prompt = baseHeader($level) . <<<PROMPT
Lag NØYAKTIG 6 grammatikkoppgaver (fyll inn).

KRAV:
- task_type = "fill"
- Ett hull (__)
- Relevant grammatikk

STRUKTUR:
{
  "task_type": "fill",
  "payload": {
    "sentence": "Setning med __",
    "answer": "riktig svar"
  }
}

TEKST:
{$cleanText}
PROMPT;
        break;

    case 'match':
        $prompt = baseHeader($level) . <<<PROMPT
Lag NØYAKTIG 3 match-oppgaver.

KRAV:
- task_type = "match"
- 3–5 elementer per oppgave
- BRUK payload.items (IKKE pairs)

STRUKTUR:
{
  "task_type": "match",
  "payload": {
    "prompt": "Koble sammen",
    "items": [
      { "left": "ord", "right": "forklaring" }
    ]
  }
}

TEKST:
{$cleanText}
PROMPT;
        break;

    case 'writing':
        $prompt = baseHeader($level) . <<<PROMPT
Lag 2–3 ALTERNATIVE SKRIVEOPPGAVER.

FORMÅL:
- Tving frem sammenhengende skriving
- 5–10 setninger
- Refleksjon eller forklaring

KRAV:
- task_type = "writing"
- Ingen fasit

STRUKTUR:
{
  "task_type": "writing",
  "payload": {
    "prompt": "Skriveoppgave"
  }
}

TEKST:
{$cleanText}
PROMPT;
        break;
}

/* --------------------------------------------------
   Kall AI
-------------------------------------------------- */
try {
    $response = call_openai($prompt);
} catch (Throwable $e) {
    echo json_encode(['error' => 'AI-feil: ' . $e->getMessage()]);
    exit;
}

/* --------------------------------------------------
   Returner rå JSON
-------------------------------------------------- */
echo $response;
