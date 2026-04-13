<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

$pdo = DatabaseManager::get('lex');

echo "=== CLEANUP START ===\n";

/* ======================================================
   1. SLETT IKKE-GODKJENTE ENTRIES
====================================================== */

echo "Deleting non-approved entries...\n";

$stmt = $pdo->exec("
    DELETE FROM lex_entries
    WHERE status != 'approved'
");

echo "Deleted (non-approved): " . $stmt . "\n";

/* ======================================================
   2. SLETT ÅPENBART ØDELAGTE LEMMA
====================================================== */

echo "Deleting broken lemma (hargjort etc)...\n";

$stmt = $pdo->exec("
    DELETE FROM lex_entries
    WHERE lemma REGEXP '^(har|hadde|skal|vil)[a-zæøå]+'
");

echo "Deleted (broken lemma): " . $stmt . "\n";

/* ======================================================
   3. SLETT ENTRIES UTEN STRUKTUR
====================================================== */

echo "Deleting entries without structured data...\n";

$stmt = $pdo->exec("
    DELETE e FROM lex_entries e
    LEFT JOIN lex_verb v ON v.entry_id = e.id
    LEFT JOIN lex_noun n ON n.entry_id = e.id
    LEFT JOIN lex_adjective a ON a.entry_id = e.id
    LEFT JOIN lex_pronoun p ON p.entry_id = e.id
    LEFT JOIN lex_determiner d ON d.entry_id = e.id
    LEFT JOIN lex_numeral num ON num.entry_id = e.id
    WHERE 
        v.entry_id IS NULL
        AND n.entry_id IS NULL
        AND a.entry_id IS NULL
        AND p.entry_id IS NULL
        AND d.entry_id IS NULL
        AND num.entry_id IS NULL
");

echo "Deleted (no structure): " . $stmt . "\n";

/* ======================================================
   4. SLETT FORMER UTEN ENTRY
====================================================== */

echo "Cleaning orphan forms...\n";

$stmt = $pdo->exec("
    DELETE f FROM lex_forms f
    LEFT JOIN lex_entries e ON e.id = f.entry_id
    WHERE e.id IS NULL
");

echo "Deleted forms: " . $stmt . "\n";

/* ======================================================
   5. SLETT SENSES UTEN ENTRY
====================================================== */

echo "Cleaning orphan senses...\n";

$stmt = $pdo->exec("
    DELETE s FROM lex_senses s
    LEFT JOIN lex_entries e ON e.id = s.entry_id
    WHERE e.id IS NULL
");

echo "Deleted senses: " . $stmt . "\n";

/* ======================================================
   6. RESET REVIEW QUEUE (VALGFRI)
====================================================== */

echo "Resetting review queue (optional)...\n";

$stmt = $pdo->exec("
    DELETE FROM lex_review_queue
    WHERE status = 'rejected'
");

echo "Deleted rejected queue: " . $stmt . "\n";

echo "=== CLEANUP DONE ===\n";