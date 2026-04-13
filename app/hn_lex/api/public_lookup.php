<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_lookup.php';
require_once __DIR__ . '/../inc/lex_missing.php';

/* --------------------------------------------------
   Logging
-------------------------------------------------- */

$logDir  = dirname(__DIR__) . '/logs';
$logFile = $logDir . '/public_lookup.log';

if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

function plog(string $msg): void {
    global $logFile;
    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL,
        FILE_APPEND
    );
}

/* --------------------------------------------------
   Input
-------------------------------------------------- */

$word  = trim((string)($_GET['word'] ?? ''));
$lang  = trim((string)($_GET['lang']  ?? 'no'));
$level = trim((string)($_GET['level'] ?? 'A2'));

if ($word === '') {
    echo json_encode(['found' => false]);
    exit;
}

$normalizedWord = mb_strtolower($word);

plog("LOOKUP word=$normalizedWord lang=$lang");

/* --------------------------------------------------
   Lookup
-------------------------------------------------- */

$entryRow = lookupLexEntry($pdo, $normalizedWord, $lang);

if (!$entryRow) {

    plog("NOT FOUND $normalizedWord");

    try {
        logMissingWord($pdo, $word, $normalizedWord, $lang);
    } catch (Throwable $e) {
        plog("Missing log failed: " . $e->getMessage());
    }

    echo json_encode([
        'found' => false,
        'lemma' => $normalizedWord
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

/* --------------------------------------------------
   Grammar
-------------------------------------------------- */

$grammar = null;

$stmt = $pdo->prepare("
    SELECT target_table
    FROM lex_word_class_targets
    WHERE word_class_id = ?
    LIMIT 1
");

$stmt->execute([$entryRow['word_class_id']]);
$target = $stmt->fetchColumn();

if ($target && preg_match('/^lex_[a-z_]+$/', $target)) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM {$target}
        WHERE entry_id = ?
        LIMIT 1
    ");

    $stmt->execute([$entryRow['id']]);
    $grammar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($grammar) {
        unset(
            $grammar['id'],
            $grammar['entry_id'],
            $grammar['created_at'],
            $grammar['updated_at']
        );
    }
}

/* --------------------------------------------------
   Explanations
-------------------------------------------------- */

$stmt = $pdo->prepare("
    SELECT level, explanation, example
    FROM lex_explanations
    WHERE entry_id = ?
      AND language = ?
");

$stmt->execute([$entryRow['id'], $lang]);

$explanations = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $explanations[$row['level']] = [
        'forklaring' => $row['explanation'],
        'example'    => $row['example'],
    ];
}

/* --------------------------------------------------
   Contract
-------------------------------------------------- */

$entry = LexContract::fromDB(
    $entryRow,
    $grammar,
    $explanations
);

$response = LexContract::toPublic($entry, $level);
$response['clicked_form'] = $normalizedWord;

plog("SUCCESS entry_id={$entryRow['id']}");

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
