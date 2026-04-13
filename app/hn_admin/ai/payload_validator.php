<?php
declare(strict_types=1);

/**
 * Validerer og normaliserer én AI-generert oppgave.
 *
 * Task types hentes dynamisk fra databasen (task_types).
 */

function validate_task(array &$task, ?string &$error = null): bool
{
    

    if (
        empty($task['task_type']) ||
        empty($task['payload']) ||
        !is_array($task['payload'])
    ) {
        $error = 'task_type eller payload mangler';
        return false;
    }

    $taskType = (string)$task['task_type'];

    /* --------------------------------------------------
       Sjekk at task_type finnes i databasen
    -------------------------------------------------- */
    $stmt = db()->prepare("
        SELECT COUNT(*) 
        FROM task_types 
        WHERE type_key = :type
    ");
    $stmt->execute(['type' => $taskType]);

    if ((int)$stmt->fetchColumn() === 0) {
        $error = 'Ukjent task_type';
        return false;
    }

    /* --------------------------------------------------
       Normaliser payload før validering
    -------------------------------------------------- */
    normalize_payload($taskType, $task['payload']);

    /* --------------------------------------------------
       Type-spesifikk validering
    -------------------------------------------------- */
    return match ($taskType) {
        'mcq'     => validate_mcq($task['payload'], $error),
        'fill'    => validate_fill($task['payload'], $error),
        'short'   => validate_short($task['payload'], $error),
        'writing' => validate_writing($task['payload'], $error),
        'order'   => validate_order($task['payload'], $error),
        'match'   => validate_match($task['payload'], $error),
        default   => ($error = 'Ukjent task_type') && false
    };
}

/* ============================================================
   NORMALISERING
   ============================================================ */

function normalize_payload(string $type, array &$p): void
{
    /* ---------------- MCQ ---------------- */
    if ($type === 'mcq') {

        if (!isset($p['prompt']) && isset($p['question'])) {
            $p['prompt'] = $p['question'];
        }

        if (!isset($p['choices'])) {
            if (isset($p['options']) && is_array($p['options'])) {
                $p['choices'] = $p['options'];
            } elseif (isset($p['answers']) && is_array($p['answers'])) {
                $p['choices'] = $p['answers'];
            }
        }

        if (!isset($p['correct_index']) && isset($p['answer_index'])) {
            $p['correct_index'] = $p['answer_index'];
        }

        if (!isset($p['correct_index']) && isset($p['choices']) && is_array($p['choices'])) {
            $answerText = $p['answer'] ?? $p['correct_answer'] ?? null;
            if ($answerText !== null) {
                $idx = array_search($answerText, $p['choices'], true);
                if ($idx !== false) {
                    $p['correct_index'] = $idx;
                }
            }
        }

        if (isset($p['feedback']) && is_array($p['feedback'])) {
            $p['feedback']['correct']   ??= '';
            $p['feedback']['incorrect'] ??= '';
        }
    }

    /* ---------------- FILL ---------------- */
    if ($type === 'fill') {

        if (!isset($p['sentence']) && isset($p['text'])) {
            $p['sentence'] = $p['text'];
        }

        if (!isset($p['answer'])) {
            if (isset($p['missing_word'])) {
                $p['answer'] = $p['missing_word'];
            } elseif (isset($p['solution'])) {
                $p['answer'] = $p['solution'];
            } elseif (isset($p['answers']) && is_array($p['answers']) && count($p['answers']) > 0) {
                $p['answer'] = $p['answers'][0];
            }
        }
    }

    /* ---------------- SHORT / WRITING ---------------- */
    if ($type === 'short' || $type === 'writing') {
        if (!isset($p['prompt']) && isset($p['question'])) {
            $p['prompt'] = $p['question'];
        }
    }

    /* ---------------- ORDER ---------------- */
    if ($type === 'order') {

        if (!isset($p['items'])) {
            if (isset($p['sequence']) && is_array($p['sequence'])) {
                $p['items'] = $p['sequence'];
            } elseif (isset($p['sentences']) && is_array($p['sentences'])) {
                $p['items'] = $p['sentences'];
            }
        }

        if (!isset($p['solution']) && isset($p['correct_order']) && is_array($p['correct_order'])) {
            $p['solution'] = $p['correct_order'];
        }

        if (!isset($p['prompt']) && isset($p['instructions'])) {
            $p['prompt'] = $p['instructions'];
        }
    }

    /* ---------------- MATCH ---------------- */
    if ($type === 'match') {

        if (!isset($p['prompt'])) {
            if (isset($p['instruction'])) {
                $p['prompt'] = $p['instruction'];
            } elseif (isset($p['instructions'])) {
                $p['prompt'] = $p['instructions'];
            }
        }

        if (!isset($p['pairs']) && isset($p['left'], $p['right']) && is_array($p['left']) && is_array($p['right'])) {
            $pairs = [];
            $n = min(count($p['left']), count($p['right']));
            for ($i = 0; $i < $n; $i++) {
                if ($p['left'][$i] !== null && $p['right'][$i] !== null) {
                    $pairs[] = [
                        'left'  => $p['left'][$i],
                        'right' => $p['right'][$i]
                    ];
                }
            }
            $p['pairs'] = $pairs;
        }

        if (!isset($p['pairs']) && isset($p['matches']) && is_array($p['matches'])) {
            $pairs = [];
            foreach ($p['matches'] as $m) {
                if (!is_array($m)) continue;
                $left  = $m['left'] ?? $m['term'] ?? null;
                $right = $m['right'] ?? $m['definition'] ?? null;
                if ($left !== null && $right !== null) {
                    $pairs[] = ['left' => $left, 'right' => $right];
                }
            }
            $p['pairs'] = $pairs;
        }
    }
}

/* ============================================================
   VALIDATORER
   ============================================================ */

function validate_mcq(array $p, ?string &$error): bool
{
    if (empty($p['prompt'])) {
        $error = 'mcq: prompt mangler';
        return false;
    }

    if (empty($p['choices']) || !is_array($p['choices']) || count($p['choices']) < 2) {
        $error = 'mcq: choices må ha minst 2 alternativer';
        return false;
    }

    if (!isset($p['correct_index']) || !is_int($p['correct_index'])) {
        $error = 'mcq: correct_index mangler';
        return false;
    }

    if ($p['correct_index'] < 0 || $p['correct_index'] >= count($p['choices'])) {
        $error = 'mcq: correct_index er ugyldig';
        return false;
    }

    return true;
}

function validate_fill(array $p, ?string &$error): bool
{
    if (empty($p['sentence'])) {
        $error = 'fill: sentence mangler';
        return false;
    }

    if (!isset($p['answer'])) {
        $error = 'fill: answer mangler';
        return false;
    }

    return true;
}

function validate_short(array $p, ?string &$error): bool
{
    if (empty($p['prompt'])) {
        $error = 'short: prompt mangler';
        return false;
    }

    return true;
}

function validate_writing(array $p, ?string &$error): bool
{
    if (empty($p['prompt'])) {
        $error = 'writing: prompt mangler';
        return false;
    }

    return true;
}

function validate_order(array $p, ?string &$error): bool
{
    if (empty($p['items']) || !is_array($p['items']) || count($p['items']) < 2) {
        $error = 'order: items må ha minst 2 elementer';
        return false;
    }

    return true;
}

function validate_match(array $p, ?string &$error): bool
{
    if (empty($p['prompt'])) {
        $error = 'match: prompt mangler';
        return false;
    }

    if (empty($p['pairs']) || !is_array($p['pairs']) || count($p['pairs']) < 2) {
        $error = 'match: minst 2 par kreves';
        return false;
    }

    foreach ($p['pairs'] as $i => $pair) {
        if (
            !isset($pair['left'], $pair['right']) ||
            trim((string)$pair['left']) === '' ||
            trim((string)$pair['right']) === ''
        ) {
            $error = "match: ugyldig par #$i";
            return false;
        }
    }

    return true;
}
