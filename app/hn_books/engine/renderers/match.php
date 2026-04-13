<?php
declare(strict_types=1);

/**
 * Renderer: match (koble par / drag-and-drop)
 *
 * Forventet payload:
 * {
 *   "pairs": [
 *     { "left": "ord", "right": "forklaring" },
 *     { "left": "begrep", "right": "forklaring" }
 *   ]
 * }
 *
 * Variabler:
 * - $task
 * - $payload
 */

$taskId = (int)($task['id'] ?? 0);
$pairs  = $payload['pairs'] ?? [];

if ($taskId <= 0 || !is_array($pairs) || count($pairs) < 2) {
    echo '<p class="task-error">Ugyldig match-oppgave.</p>';
    return;
}

/* Normaliser par */
$leftItems  = [];
$rightItems = [];
$answerMap  = [];

foreach ($pairs as $i => $pair) {
    if (
        empty($pair['left']) ||
        empty($pair['right'])
    ) {
        continue;
    }

    $leftItems[] = [
        'key'  => (string)$pair['left'],
        'text' => (string)$pair['left']
    ];

    $rightItems[] = [
        'key'  => (string)$pair['left'], // kobles mot left
        'text' => (string)$pair['right']
    ];

    $answerMap[$pair['left']] = $pair['right'];
}

if (count($leftItems) < 2) {
    echo '<p class="task-error">For få gyldige par.</p>';
    return;
}

/* Shuffle høyre side (visning) */
shuffle($rightItems);
?>

<div
    class="task-match"
    data-correct-map="<?= htmlspecialchars(json_encode($answerMap, JSON_UNESCAPED_UNICODE)) ?>"
>

    <div class="match-columns">

        <!-- Venstre kolonne: faste begreper -->
        <div class="match-column match-left">
            <?php foreach ($leftItems as $item): ?>
                <div
                    class="match-left-item"
                    data-left="<?= htmlspecialchars($item['key']) ?>"
                >
                    <?= htmlspecialchars($item['text']) ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Høyre kolonne: draggable forklaringer -->
        <div class="match-column match-right">
            <?php foreach ($rightItems as $item): ?>
                <div
                    class="match-right-item"
                    draggable="true"
                    data-right="<?= htmlspecialchars($item['text']) ?>"
                >
                    <?= htmlspecialchars($item['text']) ?>
                </div>
            <?php endforeach; ?>
        </div>

    </div>

    <div class="task-actions" style="margin-top:12px">
        <button type="button" data-action="check">
            Sjekk
        </button>
    </div>

</div>
