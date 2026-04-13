<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| batch_explain.php – KORRIGERT
|--------------------------------------------------------------------------
| ENESTE autoritative batch-innsetting av ord
| Bruker samme pipeline som CLI:
| AI → saveLexEntry()
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/lex_save.php';
require_once __DIR__ . '/../../hn_core/ai.php';

/* --------------------------------------------------
   Input
-------------------------------------------------- */
$input = json_decode(file_get_contents('php://input'), true);

$words = $input['words'] ?? [];
$lang  = $input['lang']  ?? 'no';

if (!is_array($words) || empty($words)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ingen ord mottatt']);
    exit;
}

/* --------------------------------------------------
   Resultat
-------------------------------------------------- */
$result = [
    'saved'   => [],
    'skipped' => [],
    'errors'  => [],
];

/* --------------------------------------------------
   Batch
-------------------------------------------------- */
foreach ($words as $rawWord) {

    $lemma = mb_strtolower(trim((string)$rawWord));
    if ($lemma === '') {
        continue;
    }

    /* ----------------------------------------------
       Finnes allerede?
    ---------------------------------------------- */
    $stmt = $pdo->prepare(
        "SELECT id FROM lex_entries
         WHERE lemma = ? AND language = ?
         LIMIT 1"
    );
    $stmt->execute([$lemma, $lang]);

    if ($stmt->fetchColumn()) {
        $result['skipped'][] = $lemma;
        continue;
    }

    /* ----------------------------------------------
       AI → FULL LEX ENTRY
    ---------------------------------------------- */
    try {

        // Bruk SAMME funksjon som CLI
        $aiRaw = ai_generate_lex_entry($lemma);

        // Sørg for språk
        $aiRaw['language'] = $lang;

        // Lagre direkte (samme som CLI)
        saveLexEntry($pdo, $aiRaw);

        // Fjern evt. fra missing_log
        $pdo->prepare(
            "DELETE FROM lex_missing_log
             WHERE word = ? AND language = ?"
        )->execute([$lemma, $lang]);

        $result['saved'][] = $lemma;

    } catch (Throwable $e) {
        $result['errors'][$lemma] = $e->getMessage();
    }
}

/* --------------------------------------------------
   Respons
-------------------------------------------------- */
echo json_encode(
    $result,
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
