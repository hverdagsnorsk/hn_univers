<?php
declare(strict_types=1);

namespace HnLex\Validation;

use RuntimeException;

class LexEntryValidator
{
    public function validate(array $data): void
    {
        /* ------------------------------------------------------
           ROOT
        ------------------------------------------------------ */

        if (empty($data['lemma'])) {
            throw new RuntimeException('lemma is required');
        }

        if (empty($data['word_class'])) {
            throw new RuntimeException('word_class is required');
        }

        if (!isset($data['grammar']) || !is_array($data['grammar'])) {
            throw new RuntimeException('grammar must be array');
        }

        if (!isset($data['senses']) || !is_array($data['senses']) || count($data['senses']) === 0) {
            throw new RuntimeException('at least one sense required');
        }

        /* ------------------------------------------------------
           WORD CLASS RULES
        ------------------------------------------------------ */

        $this->validateWordClass($data);

        /* ------------------------------------------------------
           SENSES
        ------------------------------------------------------ */

        foreach ($data['senses'] as $i => $sense) {

            if (empty($sense['definition'])) {
                throw new RuntimeException("sense[$i].definition missing");
            }

            if (!isset($sense['explanations']) || !is_array($sense['explanations']) || count($sense['explanations']) === 0) {
                throw new RuntimeException("sense[$i] must have explanations");
            }

            foreach ($sense['explanations'] as $j => $exp) {

                if (empty($exp['level'])) {
                    throw new RuntimeException("sense[$i].explanations[$j].level missing");
                }

                if (empty($exp['explanation'])) {
                    throw new RuntimeException("sense[$i].explanations[$j].explanation missing");
                }
            }
        }
    }

    /* ==========================================================
       WORD CLASS VALIDATION
    ========================================================== */

    private function validateWordClass(array $data): void
    {
        $wc = $data['word_class'];
        $grammar = $data['grammar'];

        switch ($wc) {

            case 'substantiv':
                if (empty($grammar['gender'])) {
                    throw new RuntimeException('substantiv requires gender');
                }
                break;

            case 'verb':
                if (empty($grammar['infinitive'])) {
                    throw new RuntimeException('verb requires infinitive');
                }
                break;

            case 'adjektiv':
                // kan utvides senere
                break;

            case 'adverb':
                // ofte tom grammar → OK
                break;

            default:
                throw new RuntimeException("unsupported word_class: {$wc}");
        }
    }
}