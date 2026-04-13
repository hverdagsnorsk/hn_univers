<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/bootstrap.php';

/* --------------------------------------------------
   Input (JSON POST)
-------------------------------------------------- */
$in = json_decode(file_get_contents('php://input'), true);

$word = trim(mb_strtolower((string)($in['word'] ?? '')));
$lang = trim((string)($in['lang'] ?? 'no'));

if ($word === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Mangler word']);
    exit;
}

/* --------------------------------------------------
   Finnes ordet allerede i lex_entries?
   → da skal det IKKE inn i lex_missing_log
-------------------------------------------------- */
$stmt = $pdo->prepare(
    "SELECT id
     FROM lex_entries
     WHERE lemma = ? AND language = ?
     LIMIT 1"
);
$stmt->execute([$word, $lang]);

if ($stmt->fetchColumn()) {
    // Ordet finnes allerede – ingenting å logge
    echo json_encode(['ok' => true, 'skipped' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

/* --------------------------------------------------
   Best-effort kontekst (fra referer)
-------------------------------------------------- */
$bookSlug = null;
$textId   = null;

if (!empty($_SERVER['HTTP_REFERER'])) {
    // Eksempel:
    // /hn_books/books/consolvo/texts/Consolvo-002.html
    if (preg_match('#/books/([^/]+)/#', $_SERVER['HTTP_REFERER'], $m)) {
        $bookSlug = $m[1];
    }
    if (preg_match('#/texts/([^/.]+)#', $_SERVER['HTTP_REFERER'], $m)) {
        $textId = $m[1];
    }
}

/* --------------------------------------------------
   UPSERT – ett oppslag pr (ord + språk + kontekst)
-------------------------------------------------- */
$stmt = $pdo->prepare("
    INSERT INTO lex_missing_log
      (word, language, book_slug, text_id, clicks, last_seen)
    VALUES
      (?, ?, ?, ?, 1, NOW())
    ON DUPLICATE KEY UPDATE
      clicks = clicks + 1,
      last_seen = NOW()
");

$stmt->execute([
    $word,
    $lang,
    $bookSlug,
    $textId
]);

echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
