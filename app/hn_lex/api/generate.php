<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';
require_once __DIR__ . '/../../hn_core/ai.php';

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ugyldig JSON']);
    exit;
}

$lemma     = trim((string)($input['lemma'] ?? ''));
$ordklasse = trim((string)($input['ordklasse'] ?? ''));
$language  = trim((string)($input['language'] ?? 'no'));

if ($lemma === '' || $ordklasse === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mangler lemma eller ordklasse']);
    exit;
}

/* --------------------------------------------------
   Valider ordklasse mot DB
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT code
     FROM lex_word_classes
     WHERE code = ?
     LIMIT 1"
);
$stmt->execute([$ordklasse]);

if (!$stmt->fetchColumn()) {
    http_response_code(400);
    echo json_encode(['error' => "Ukjent ordklasse: {$ordklasse}"]);
    exit;
}

/* --------------------------------------------------
   Bygg grammatikkstruktur fra LexContract
-------------------------------------------------- */
$schema = LexContract::getGrammarSchema($ordklasse);

$grammarSpec = '"grammar": null';

if ($schema) {

    $fields = array_merge(
        $schema['required'] ?? [],
        $schema['optional'] ?? []
    );

    $lines = [];
    foreach ($fields as $f) {
        $lines[] = "\"{$f}\": \"\"";
    }

    $grammarSpec =
        "\"grammar\": {\n      " .
        implode(",\n      ", $lines) .
        "\n    }";
}

/* --------------------------------------------------
   AI-prompt
-------------------------------------------------- */
$systemPrompt =
    "Du er en norsk leksikograf.\n\n" .
    "VIKTIG:\n" .
    "- Returner KUN gyldig JSON\n" .
    "- Ingen markdown\n" .
    "- Ingen forklarende tekst\n";

$userPrompt = <<<TXT
Lag et fullstendig norsk ordbokoppslag.

JSON-format (MÅ følges):

{
  "lemma": "",
  "ordklasse": "{$ordklasse}",
  {$grammarSpec},
  "explanations": {
    "A2": {
      "forklaring": "",
      "example": ""
    }
  }
}

ORD:
"{$lemma}"
TXT;

/* --------------------------------------------------
   Kall AI
-------------------------------------------------- */
try {

    $payload = [
        'model' => 'gpt-3.5-turbo-0125',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $userPrompt],
        ],
        'temperature' => 0.2,
    ];

    $json = openai_request($payload);
    $raw  = trim($json['choices'][0]['message']['content'] ?? '');

    $aiRaw = json_decode($raw, true);

    if (!is_array($aiRaw) && preg_match('/\{.*\}/s', $raw, $m)) {
        $aiRaw = json_decode($m[0], true);
    }

    if (!is_array($aiRaw)) {
        throw new RuntimeException("Ugyldig JSON fra AI:\n{$raw}");
    }

    /* ------------------------------
       DEBUG 1 – RÅ AI-DATA
    ------------------------------ */
    file_put_contents(
        __DIR__ . '/../debug_ai.json',
        json_encode($aiRaw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'error' => 'AI-feil: ' . $e->getMessage()
    ]);
    exit;
}

/* --------------------------------------------------
   Normaliser via kontrakt
-------------------------------------------------- */
try {

    $aiRaw['lemma']     = $lemma;
    $aiRaw['language']  = $language;
    $aiRaw['ordklasse'] = $ordklasse;

    $entry = LexContract::fromAI($aiRaw);

    /* ------------------------------
       DEBUG 2 – KONTRAKT-DATA
    ------------------------------ */
    file_put_contents(
        __DIR__ . '/../debug_contract.json',
        json_encode($entry, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'error' => 'Kontraktfeil: ' . $e->getMessage(),
        'raw'   => $aiRaw ?? null
    ]);
    exit;
}

/* --------------------------------------------------
   Lagre
-------------------------------------------------- */
try {

    $entryId = saveLexEntry($pdo, $entry);

    echo json_encode([
        'ok'       => true,
        'entry_id' => $entryId
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    http_response_code(500);
    echo json_encode([
        'error' => 'DB-feil: ' . $e->getMessage()
    ]);
}
