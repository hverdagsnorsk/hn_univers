<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$in = json_decode(file_get_contents('php://input'), true);

$word  = trim(mb_strtolower((string)($in['word']  ?? '')));
$block = trim((string)($in['block'] ?? ''));
$level = trim((string)($in['level'] ?? 'A2'));
$lang  = trim((string)($in['lang']  ?? 'no'));

if ($word === '' || $block === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mangler word eller block']);
    exit;
}

/* --------------------------------------------------
   1. DB: Finnes forklaring allerede?
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT e.id
     FROM lex_entries e
     WHERE e.lemma = ? AND e.language = ?
     LIMIT 1"
);
$stmt->execute([$word, $lang]);
$entryId = $stmt->fetchColumn();

if ($entryId) {
    $stmt = $pdo->prepare(
        "SELECT explanation, example
         FROM lex_explanations
         WHERE entry_id = ?
           AND level = ?
           AND language = ?
         LIMIT 1"
    );
    $stmt->execute([$entryId, $level, $lang]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        echo json_encode([
            'explanation' => $row['explanation'],
            'example'     => $row['example']
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/* --------------------------------------------------
   2. API-nøkkel
-------------------------------------------------- */
$apiKey = $_ENV['OPENAI_API_KEY'] ?? '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['error' => 'OPENAI_API_KEY mangler']);
    exit;
}

/* --------------------------------------------------
   3. Fil-cache (sekundær cache)
-------------------------------------------------- */
$cacheDir = __DIR__ . '/../cache/explain';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0775, true);
}

$cacheKey  = sha1("$lang|$level|$word|" . substr(sha1($block), 0, 12));
$cacheFile = "$cacheDir/$cacheKey.json";

if (is_file($cacheFile)) {
    echo file_get_contents($cacheFile);
    exit;
}

/* --------------------------------------------------
   4. Prompt
-------------------------------------------------- */
$system = "Du forklarer ord i norsk tekst for andrespråksinnlærere på nivå $level.";

$user =
    "Ord: \"$word\"\n\n" .
    "Kontekst:\n\"$block\"\n\n" .
    "Svar i to korte avsnitt:\n" .
    "1) Kort forklaring\n" .
    "2) Ett enkelt eksempel";

/* --------------------------------------------------
   5. OpenAI-kall
-------------------------------------------------- */
$payload = [
    'model' => 'gpt-3.5-turbo-0125',
    'messages' => [
        ['role' => 'system', 'content' => $system],
        ['role' => 'user',   'content' => $user],
    ],
    'temperature' => 0.2,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 30,
]);

$response = curl_exec($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL-feil', 'detail' => curl_error($ch)]);
    exit;
}

$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status !== 200) {
    http_response_code(500);
    echo json_encode(['error' => "OpenAI-feil (HTTP $status)", 'raw' => $response]);
    exit;
}

/* --------------------------------------------------
   6. Parse + cache
-------------------------------------------------- */
$json = json_decode($response, true);
$text = trim($json['choices'][0]['message']['content'] ?? '');

$parts = preg_split('/\n\s*\n/', $text, 2);

$out = [
    'explanation' => $parts[0] ?? $text,
    'example'     => $parts[1] ?? '',
];

file_put_contents(
    $cacheFile,
    json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
);

echo json_encode($out, JSON_UNESCAPED_UNICODE);
