<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/ai_client.php';

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$in = json_decode(file_get_contents('php://input'), true);

$entryId = (int)($in['entry_id'] ?? 0);
$levels  = $in['levels'] ?? ['A2', 'B1'];

if ($entryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Mangler entry_id']);
    exit;
}

/* --------------------------------------------------
   Hent lemma + språk + word_class_id
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT lemma, language, word_class_id
     FROM lex_entries
     WHERE id = ?"
);
$stmt->execute([$entryId]);
$entry = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entry) {
    http_response_code(404);
    echo json_encode(['error' => 'Oppslag ikke funnet']);
    exit;
}

/* --------------------------------------------------
   Hent ordklasse (DB-korrekt)
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT code
     FROM lex_word_classes
     WHERE id = ?
     LIMIT 1"
);
$stmt->execute([$entry['word_class_id']]);
$ordklasse = $stmt->fetchColumn() ?: 'substantiv';

/* --------------------------------------------------
   AI-prompt
-------------------------------------------------- */
$system = <<<TXT
Du er en norsk språkekspert.

Du skal generere NATURLIGE og PEDAGOGISKE
eksempelsetninger i norsk bokmål
for andrespråksinnlærere.

KRAV:
- RETURNER KUN GYLDIG JSON
- IKKE forklar noe
- IKKE bruk markdown
TXT;

$user = json_encode([
    'task'      => 'generate_examples',
    'lemma'     => $entry['lemma'],
    'ordklasse' => $ordklasse,
    'language'  => $entry['language'],
    'levels'    => $levels
], JSON_UNESCAPED_UNICODE);

/* --------------------------------------------------
   Kall AI
-------------------------------------------------- */
$raw  = callAI($system, $user);
$data = json_decode($raw, true);

if (!$data || empty($data['examples']) || !is_array($data['examples'])) {
    http_response_code(500);
    echo json_encode([
        'error' => 'AI-respons ugyldig',
        'raw'   => $raw
    ]);
    exit;
}

/* --------------------------------------------------
   Lagre (erstatt gamle)
-------------------------------------------------- */
$pdo->beginTransaction();

try {
    $pdo->prepare(
        "DELETE FROM lex_examples WHERE entry_id = ?"
    )->execute([$entryId]);

    $stmt = $pdo->prepare(
        "INSERT INTO lex_examples
         (entry_id, sentence, level, language, source)
         VALUES (?, ?, ?, ?, 'ai_regenerated')"
    );

    foreach ($data['examples'] as $ex) {
        if (empty($ex['sentence'])) {
            continue;
        }

        $stmt->execute([
            $entryId,
            $ex['sentence'],
            $ex['level'] ?? null,
            $ex['language'] ?? $entry['language']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'ok'    => true,
        'count' => count($data['examples'])
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
