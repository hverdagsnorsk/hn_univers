<?php
declare(strict_types=1);

/* ==========================================================
   BOOTSTRAP
========================================================== */

require_once dirname(__DIR__, 3) . '/app/vendor/autoload.php';
require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnLex\Service\LookupService;
use HnLex\Service\LexStorageService;
use HnLex\Service\EmbeddingService;

/* ========================================================== */

header('Content-Type: application/json; charset=utf-8');

static $requestCache = [];

/* ==========================================================
   INPUT
========================================================== */

$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);

if (is_array($json)) {

    $word = trim((string)($json['word'] ?? ''));

    $tokens = [];
    if (!empty($json['tokens'])) {
        $decoded = is_array($json['tokens'])
            ? $json['tokens']
            : json_decode((string)$json['tokens'], true);

        if (is_array($decoded)) {
            $tokens = $decoded;
        }
    }

    $context = [
        'word'       => $word,
        'prev'       => $json['prev'] ?? '',
        'next'       => $json['next'] ?? '',
        'sentence'   => trim($json['sentence'] ?? $json['context'] ?? ''),
        'paragraph'  => trim($json['paragraph'] ?? ''),
        'word_index' => isset($json['word_index']) ? (int)$json['word_index'] : null,
        'tokens'     => $tokens
    ];

    $level    = $json['level'] ?? 'A2';
    $language = $json['language'] ?? 'nb';

} else {

    $word =
        $_GET['word']
        ?? $_GET['v']
        ?? $_GET['q']
        ?? '';

    $word = trim((string)$word);

    $context = [
        'word'       => $word,
        'prev'       => $_GET['prev'] ?? '',
        'next'       => $_GET['next'] ?? '',
        'sentence'   => trim($_GET['sentence'] ?? ''),
        'paragraph'  => trim($_GET['paragraph'] ?? ''),
        'word_index' => isset($_GET['word_index']) ? (int)$_GET['word_index'] : null,
        'tokens'     => []
    ];

    $level    = $_GET['level'] ?? 'A2';
    $language = $_GET['lang'] ?? 'nb';
}

/* ========================================================== */

if ($word === '') {
    echo json_encode([
        'found' => false,
        'error' => 'missing word'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ==========================================================
   CACHE
========================================================== */

$cacheKey = md5(
    $word . '|' .
    ($context['sentence'] ?? '') . '|' .
    ($context['prev'] ?? '') . '|' .
    ($context['next'] ?? '') . '|' .
    ($context['word_index'] ?? '')
);

if (isset($requestCache[$cacheKey])) {
    echo json_encode($requestCache[$cacheKey], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ==========================================================
   SERVICES
========================================================== */

$pdo = DatabaseManager::get('lex');

$storage = new LexStorageService($pdo);

/* embedding */
$embedding = null;
$key = $_ENV['OPENAI_API_KEY'] ?? '';

if ($key !== '') {
    try {
        $embedding = new EmbeddingService($key);
    } catch (Throwable) {
        $embedding = null;
    }
}

$lookup = new LookupService(
    $pdo,
    $storage,
    $embedding
);

/* ==========================================================
   LOOKUP
========================================================== */

try {

    $result = $lookup->lookup($word, $language, $level, $context);

    /* ======================================================
       TREFF
    ====================================================== */

    if (
        !empty($result['found']) &&
        $result['found'] === true &&
        !empty($result['entry_id'])
    ) {

        lookupApiLogDisambiguation($pdo, $result, $context);

        returnJson($requestCache, $cacheKey, $result);
    }

    /* ======================================================
       LOG MISSING
    ====================================================== */

    $pdo->prepare("
        INSERT INTO lex_missing_log (word, language, created_at, clicks)
        VALUES (?, ?, NOW(), 1)
        ON DUPLICATE KEY UPDATE
            updated_at = NOW(),
            clicks = clicks + 1
    ")->execute([$word, $language]);

    /* ======================================================
       STAGING
    ====================================================== */

    $pdo->prepare("
        INSERT INTO lex_entries_staging
        (lemma, word_class, payload_json, status, source, created_at)
        VALUES (?, '', '{}', 'pending', 'lookup', NOW())
        ON DUPLICATE KEY UPDATE
            created_at = created_at
    ")->execute([$word]);

    /* ======================================================
       AI JOB (KOBLING TIL hn_core/ai)
    ====================================================== */

    $pdo->prepare("
        INSERT INTO lex_ai_jobs (entry_id, status, job_type, created_at)
        SELECT id, 'pending', 'generate_entry', NOW()
        FROM lex_entries_staging
        WHERE lemma = ?
        AND status = 'pending'
        LIMIT 1
    ")->execute([$word]);

    /* ====================================================== */

    returnJson($requestCache, $cacheKey, [
        'found'  => false,
        'query'  => $word,
        'queued' => true
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'found' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ==========================================================
   HELPERS
========================================================== */

function lookupApiLogDisambiguation(PDO $pdo, array $result, array $context): void
{
    if (empty($result['sense_id'])) {
        return;
    }

    $pdo->prepare("
        INSERT INTO lex_disambiguation_log
        (word, prev_word, next_word, chosen_sense_id)
        VALUES (?, ?, ?, ?)
    ")->execute([
        $context['word'],
        $context['prev'],
        $context['next'],
        (int)$result['sense_id']
    ]);
}

function returnJson(array &$cache, string $key, array $data): void
{
    $cache[$key] = $data;
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}