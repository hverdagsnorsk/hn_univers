<?php
/**
 * Renderer: Order (rekkefølge)
 *
 * Forventet payload:
 * {
 *   "prompt": "Sett ordene i riktig rekkefølge",
 *   "items": ["jeg", "jobber", "i", "barnehage"]
 * }
 *
 * Variabler tilgjengelig:
 * - $task
 * - $payload
 */

$taskId = (int)($task['id'] ?? 0);
$items  = $payload['items'] ?? [];

if ($taskId <= 0 || !is_array($items) || count($items) < 2) {
    echo '<p class="task-error">Ugyldig rekkefølgeoppgave.</p>';
    return;
}

/* Bland visningen, men behold fasit */
$shuffled = $items;
shuffle($shuffled);
?>

<div
    class="task-order"
    data-correct-order="<?= htmlspecialchars(json_encode($items, JSON_UNESCAPED_UNICODE)) ?>"
>

    <ul class="order-items">
        <?php foreach ($shuffled as $i => $word): ?>
            <li class="order-item">
                <input
                    type="number"
                    min="1"
                    max="<?= count($items) ?>"
                    class="order-input"
                    data-word="<?= htmlspecialchars($word) ?>"
                >
                <span><?= htmlspecialchars($word) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="task-actions" style="margin-top:10px">
        <button
            type="button"
            data-action="check"
        >
            Sjekk
        </button>
    </div>

</div>
