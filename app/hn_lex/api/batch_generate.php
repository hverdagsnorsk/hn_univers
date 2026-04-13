<?php
declare(strict_types=1);

/* ============================================================
   LANG KJØRING / IKKE AVBRYT
============================================================ */
set_time_limit(0);
ignore_user_abort(true);

/* ============================================================
   BOOTSTRAP + FELLESFUNKSJONER
============================================================ */
require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/lex_lookup.php';

/* 🔑 FELLES AI (ABSOLUTT STI) */
require_once __DIR__ . '/../../hn_core/ai.php';

/* ============================================================
   ENKEL DEBUG-LOGG
============================================================ */
$debugFile = __DIR__ . '/batch_debug.log';
file_put_contents($debugFile, date('c') . " batch start\n", FILE_APPEND);

/* ============================================================
   STREAMING-SAFE SETUP
============================================================ */
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
ini_set('zlib.output_compression', '0');

while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

/* ============================================================
   HEADERS (KRITISK)
============================================================ */
header('Content-Type: application/x-ndjson; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

/* ============================================================
   INPUT
============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$items = $_POST['words'] ?? [];
if (!is_array($items) || !$items) {
    file_put_contents($debugFile, "no items\n", FILE_APPEND);
    exit;
}

$total     = count($items);
$processed = 0;

/* ============================================================
   HJELPERE
============================================================ */
function send(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
    flush();
}

function getWordClassId(PDO $pdo, string $code): int {
    $stmt = $pdo->prepare(
        "SELECT id FROM lex_word_classes WHERE code = ? LIMIT 1"
    );
    $stmt->execute([$code]);
    $id = (int)$stmt->fetchColumn();

    if ($id <= 0) {
        throw new RuntimeException("Ukjent ordklasse: {$code}");
    }
    return $id;
}

/* ============================================================
   BATCH
============================================================ */
foreach ($items as $item) {
    $processed++;

    send([
        'type'      => 'progress',
        'processed' => $processed,
        'total'     => $total
    ]);

    if (!str_contains($item, '|')) {
        continue;
    }

    [$word, $lang] = explode('|', $item, 2);
    $word = trim(mb_strtolower($word));
    $lang = trim($lang ?: 'no');

    if ($word === '') {
        continue;
    }

    /* --------------------------------------------
       Finnes allerede i lex_entries?
    -------------------------------------------- */
    if (lookupLexEntry($pdo, $word, $lang)) {
        $pdo->prepare(
            "DELETE FROM lex_missing_log
             WHERE word = ? AND language = ?"
        )->execute([$word, $lang]);
        continue;
    }

    /* --------------------------------------------
       AI-generering
    -------------------------------------------- */
    try {
        $ai = ai_generate_lex_entry($word);
    } catch (Throwable $e) {
        file_put_contents(
            $debugFile,
            "AI error {$word}: {$e->getMessage()}\n",
            FILE_APPEND
        );
        continue;
    }

    $lemma     = trim($ai['lemma'] ?? '');
    $forkl     = trim($ai['forklaring'] ?? '');
    $eksempel  = trim($ai['eksempel'] ?? '');
    $ordklasse = mb_strtolower(trim($ai['ordklasse'] ?? ''));

    if ($lemma === '' || $forkl === '') {
        file_put_contents(
            $debugFile,
            "AI missing fields {$word}\n",
            FILE_APPEND
        );
        continue;
    }

    /* Normaliser ordklasse */
    $ordklasse = preg_replace('/[^a-zæøå]/u', '', $ordklasse) ?: 'substantiv';

    try {
        $wordClassId = getWordClassId($pdo, $ordklasse);
    } catch (Throwable) {
        $wordClassId = getWordClassId($pdo, 'substantiv');
    }

    /* --------------------------------------------
       DB
    -------------------------------------------- */
    try {
        $pdo->beginTransaction();

        /* lex_entries */
        $pdo->prepare(
            "INSERT INTO lex_entries
                (lemma, language, word_class_id, source)
             VALUES
                (?, ?, ?, 'ai')"
        )->execute([$lemma, $lang, $wordClassId]);

        $entryId = (int)$pdo->lastInsertId();

        /* lex_explanations */
        $pdo->prepare(
            "INSERT INTO lex_explanations
                (entry_id, level, language, explanation, example, source)
             VALUES
                (?, 'A2', ?, ?, ?, 'ai')"
        )->execute([$entryId, $lang, $forkl, $eksempel]);

        /* rydde mangellogg */
        $pdo->prepare(
            "DELETE FROM lex_missing_log
             WHERE word = ? AND language = ?"
        )->execute([$word, $lang]);

        $pdo->commit();

    } catch (Throwable $e) {
        $pdo->rollBack();
        file_put_contents(
            $debugFile,
            "DB error {$word}: {$e->getMessage()}\n",
            FILE_APPEND
        );
    }
}

/* ============================================================
   FERDIG
============================================================ */
send(['type' => 'done']);
file_put_contents($debugFile, "batch done\n", FILE_APPEND);
