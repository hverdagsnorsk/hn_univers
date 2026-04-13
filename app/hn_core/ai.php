<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN CORE – AI WRAPPER LAYER
|--------------------------------------------------------------------------
| Thin legacy compatibility layer for AI services.
| All real logic lives inside src/Ai/Services.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/inc/bootstrap.php';

/*
|--------------------------------------------------------------------------
| USE (PSR-4 – KORREKT)
|--------------------------------------------------------------------------
*/

use HnCore\Ai\Services\OpenAiLexService;

/*
|--------------------------------------------------------------------------
| Internal Service Resolver (Singleton)
|--------------------------------------------------------------------------
*/

function hn_lex_service(): OpenAiLexService
{
    static $service = null;

    if ($service instanceof OpenAiLexService) {
        return $service;
    }

    $service = new OpenAiLexService();

    return $service;
}

/*
|--------------------------------------------------------------------------
| Legacy Compatibility Layer – DO NOT REMOVE
|--------------------------------------------------------------------------
*/

/* ==========================================================
   AUTO WORD CLASS
========================================================== */

function ai_generate_lex_entry(string $word): array
{
    return hn_lex_service()->generateEntry($word);
}

/* ==========================================================
   FORCED WORD CLASS
========================================================== */

function ai_generate_lex_entry_forced(
    string $word,
    string $forcedClass
): array {
    return hn_lex_service()->generateEntryForced(
        $word,
        $forcedClass
    );
}

/* ==========================================================
   STRICT BATCH
========================================================== */

function ai_generate_lex_entries_batch(array $words): array
{
    return hn_lex_service()->generateBatch($words);
}