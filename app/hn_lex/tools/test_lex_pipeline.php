<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| test_lex_pipeline.php
|--------------------------------------------------------------------------
| CLI-test av HEL Lex-pipeline:
| AI → LexContract → saveLexEntry
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../inc/bootstrap.php';
require_once __DIR__ . '/../inc/contracts/LexContract.php';
require_once __DIR__ . '/../inc/lex_save.php';
require_once __DIR__ . '/../../hn_core/ai.php';

echo "=== LEX PIPELINE TEST ===\n\n";

$testWords = [
    'forsiktig',
    'vanligvis',
    'begynner',
    'firmabilen',
    'du',
];

$lang = 'no';

foreach ($testWords as $word) {
    echo "----------------------------------------\n";
    echo "Ord: {$word}\n";

    try {
        // 1. AI
        $aiRaw = ai_generate_lex_entry($word);
        echo "AI: OK\n";

        // 2. Kontrakt
        $entry = LexContract::fromAI($aiRaw);
        echo "Contract: OK ({$entry['ordklasse']})\n";

        // 3. Lagring
        $id = saveLexEntry($pdo, $entry);
        echo "DB: OK (entry_id={$id})\n";

    } catch (Throwable $e) {
        echo "FEIL: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "=== FERDIG ===\n";
