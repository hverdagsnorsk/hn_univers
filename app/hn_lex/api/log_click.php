<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';

$in = json_decode(file_get_contents('php://input'), true);

$word = trim((string)($in['word'] ?? ''));
$lang = trim((string)($in['language'] ?? 'no'));

if ($word === '') {
    echo json_encode(['ok' => false, 'error' => 'missing word']);
    exit;
}

$lemma   = isset($in['lemma']) ? trim((string)$in['lemma']) : null;
$found   = (int)($in['found'] ?? 0);
$page    = isset($in['page']) ? trim((string)$in['page']) : null;
$entryId = null;

/* --------------------------------------------------
   Finn entry_id automatisk dersom ordet er funnet
-------------------------------------------------- */
if ($found === 1) {
    if ($lemma === null || $lemma === '') {
        $lemma = $word;
    }

    $stmt = $pdo->prepare(
        "SELECT id
         FROM lex_entries
         WHERE lemma = ? AND language = ?
         LIMIT 1"
    );
    $stmt->execute([$lemma, $lang]);
    $entryId = $stmt->fetchColumn() ?: null;
}

/* --------------------------------------------------
   Logg klikk
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "INSERT INTO lex_clicks
     (word, lemma, entry_id, found, language, page)
     VALUES (?, ?, ?, ?, ?, ?)"
);

$stmt->execute([
    $word,
    $lemma,
    $entryId,
    $found,
    $lang,
    $page
]);

echo json_encode(['ok' => true]);
