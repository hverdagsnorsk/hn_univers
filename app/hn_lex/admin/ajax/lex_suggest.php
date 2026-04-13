<?php
declare(strict_types=1);

/*
|------------------------------------------------------------------
| hn_lex/admin/ajax/lex_suggest.php
|------------------------------------------------------------------
| - AJAX endpoint for lemma suggestions
| - Returns JSON: [{id, lemma, pos, preview}, ...]
|------------------------------------------------------------------
*/

$root = dirname(__DIR__, 3);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 10);

if ($limit < 1)  $limit = 1;
if ($limit > 20) $limit = 20;

if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Forventet: $pdo_lex finnes fra bootstrap.
 * Hvis din PDO heter noe annet, endre her.
 */
if (!isset($pdo_lex) || !($pdo_lex instanceof PDO)) {
    http_response_code(500);
    echo json_encode(['error' => 'Lex DB not available'], JSON_UNESCAPED_UNICODE);
    exit;
}

/*
 * Tilpass SQL til din modell:
 * - Tabell: lex_entries
 * - Felter: id, lemma
 * - valgfritt: pos / part_of_speech eller type
 * - valgfritt: short_explanation / gloss / meaning for preview
 */
$sql = "
    SELECT
        e.id,
        e.lemma,
        COALESCE(e.pos, '') AS pos,
        COALESCE(e.short_explanation, '') AS preview
    FROM lex_entries e
    WHERE e.lemma LIKE :prefix
       OR e.lemma LIKE :infix
    ORDER BY
        CASE WHEN e.lemma LIKE :prefix THEN 0 ELSE 1 END,
        CHAR_LENGTH(e.lemma) ASC,
        e.lemma ASC
    LIMIT {$limit}
";

$prefix = $q . '%';
$infix  = '%' . $q . '%';

$stmt = $pdo_lex->prepare($sql);
$stmt->execute([
    ':prefix' => $prefix,
    ':infix'  => $infix,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// “hardening” av output
$out = [];
foreach ($rows as $r) {
    $out[] = [
        'id'      => (int)($r['id'] ?? 0),
        'lemma'   => (string)($r['lemma'] ?? ''),
        'pos'     => (string)($r['pos'] ?? ''),
        'preview' => (string)($r['preview'] ?? ''),
    ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);