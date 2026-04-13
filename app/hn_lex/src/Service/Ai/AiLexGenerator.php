<?php
declare(strict_types=1);

namespace HnLex\Service\Ai;

final class AiLexGenerator
{
    public function generate(string $word): array
    {
        $word = trim(mb_strtolower($word));

        if ($word === '') {
            throw new \InvalidArgumentException('Empty word');
        }

        /* ======================================================
           LOAD CORE AI (once)
        ====================================================== */

        require_once HN_ROOT . '/hn_core/ai.php';

        /* ======================================================
           CALL REAL AI
        ====================================================== */

        $data = ai_generate_lex_entry($word);

        if (!is_array($data)) {
            throw new \RuntimeException('AI returned invalid response');
        }

        /* ======================================================
           HARD VALIDATION (kritisk!)
        ====================================================== */

        if (empty($data['lemma']) || empty($data['word_class'])) {
            throw new \RuntimeException('AI response missing required fields');
        }

        return $data;
    }
}