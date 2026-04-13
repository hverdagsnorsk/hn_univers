<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/tasks.php
| Admin – Oppgaver (HN 2026 layout)
| - Bulk actions (approve/archive/delete)
| - Single status update (PRG)
| - Filter by text/status/type
| - Table in unified admin.css style
| - No inline CSS
|--------------------------------------------------------------------------*/

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';
require_once __DIR__ . '/bootstrap.php';

$page_title  = "Oppgaver";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';

/* --------------------------------------------------
   BULK handlinger (PRG)
-------------------------------------------------- */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['bulk_action'])
    && !empty($_POST['task_ids'])
    && is_array($_POST['task_ids'])
) {
    $ids = array_values(array_filter(array_map('intval', $_POST['task_ids']), fn($v) => $v > 0));
    if ($ids) {
        $in = implode(',', array_fill(0, count($ids), '?'));

        if ($_POST['bulk_action'] === 'delete') {
            $stmt = db()->prepare("DELETE FROM tasks WHERE id IN ($in)");
            $stmt->execute($ids);
        }

        if ($_POST['bulk_action'] === 'archive') {
            $stmt = db()->prepare("UPDATE tasks SET status='archived' WHERE id IN ($in)");
            $stmt->execute($ids);
        }

        if ($_POST['bulk_action'] === 'approve') {
            $stmt = db()->prepare("UPDATE tasks SET status='approved' WHERE id IN ($in)");
            $stmt->execute($ids);
        }
    }

    header('Location: tasks.php?' . http_build_query($_GET));
    exit;
}

/* --------------------------------------------------
   Oppdater status (enkeltoppgave, PRG)
-------------------------------------------------- */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['task_id'], $_POST['new_status'])
    && !isset($_POST['bulk_action'])
) {
    $tid = (int)$_POST['task_id'];
    $new = (string)$_POST['new_status'];

    if ($tid > 0 && in_array($new, ['draft', 'approved', 'archived'], true)) {
        $stmt = db()->prepare("
            UPDATE tasks
            SET status = :status
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([
            'status' => $new,
            'id'     => $tid,
        ]);
    }

    header('Location: tasks.php?' . http_build_query($_GET));
    exit;
}

/* --------------------------------------------------
   Tekster (filter)
-------------------------------------------------- */
$texts = db()->query("
    SELECT id, title
    FROM texts
    ORDER BY title
")->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   (Valgfritt) task_types – fallback
-------------------------------------------------- */
$taskTypeMap = [
    'mcq'     => 'Flervalg',
    'short'   => 'Åpent spørsmål',
    'fill'    => 'Fyll inn',
    'match'   => 'Koble sammen',
    'writing' => 'Skriveoppgave',
];

try {
    $rows = db()->query("SELECT code, label FROM task_types ORDER BY sort_order ASC, code ASC")->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        foreach ($rows as $r) {
            $code = (string)($r['code'] ?? '');
            $lab  = (string)($r['label'] ?? '');
            if ($code !== '' && $lab !== '') {
                $taskTypeMap[$code] = $lab;
            }
        }
    }
} catch (Throwable $e) {
    // ignorer – fallback brukes
}

/* --------------------------------------------------
   Filter
-------------------------------------------------- */
$where  = [];
$params = [];

if (!empty($_GET['text_id'])) {
    $where[] = 't.text_id = :text_id';
    $params['text_id'] = (int)$_GET['text_id'];
}

if (!empty($_GET['status'])) {
    $st = (string)$_GET['status'];
    if (in_array($st, ['draft', 'approved', 'archived'], true)) {
        $where[] = 't.status = :status';
        $params['status'] = $st;
    }
}

if (!empty($_GET['type'])) {
    $tp = (string)$_GET['type'];
    if ($tp !== '') {
        $where[] = 't.task_type = :type';
        $params['type'] = $tp;
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* --------------------------------------------------
   Oppgaver
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT
        t.id,
        t.task_type,
        t.status,
        t.created_at,
        x.title AS text_title,
        t.payload_json
    FROM tasks t
    JOIN texts x ON x.id = t.text_id
    $whereSql
    ORDER BY t.created_at DESC
");
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Hjelpere
-------------------------------------------------- */
function type_label(string $type, array $map): string {
    return $map[$type] ?? $type;
}

function preview_text(string $type, array $payload): string {
    $prompt = (string)($payload['prompt'] ?? '');

    if ($type === 'mcq' || $type === 'short' || $type === 'writing') {
        return $prompt !== '' ? $prompt : '—';
    }

    if ($type === 'fill') {
        $s = (string)($payload['sentence'] ?? '');
        return $s !== '' ? $s : '—';
    }

    if ($type === 'match') {
        if (isset($payload['pairs']) && is_array($payload['pairs'])) {
            $n = count($payload['pairs']);
            return $n > 0 ? ('Koble sammen (' . $n . ' par)') : 'Koble sammen (0 par)';
        }
        if (isset($payload['items']) && is_array($payload['items'])) {
            $n = count($payload['items']);
            return $n > 0 ? ('Koble sammen (' . $n . ' par)') : 'Koble sammen (0 par)';
        }
        return 'Koble sammen';
    }

    return $prompt !== '' ? $prompt : '—';
}

function badge_class(string $status): string {
    return match ($status) {
        'approved' => 'badge-success',
        'draft'    => 'badge-warning',
        'archived' => 'badge-muted',
        default    => 'badge-muted',
    };
}

function has_any_filter(): bool {
    return !empty($_GET['text_id']) || !empty($_GET['status']) || !empty($_GET['type']);
}

?>

<main class="page admin-page">

<header class="page-header">
    <h1>Oppgaver</h1>
    <p class="page-intro">
        AI-genererte og redigerte oppgaver. Filtrer, bulk-behandle og åpne for redigering.
    </p>
    <p style="margin-top:12px;">
        <a href="index.php" class="hn-btn secondary">← Tilbake</a>
    </p>
</header>

<section class="admin-card-section">

    <!-- TOOLBAR -->
    <div class="admin-toolbar-card">

        <div class="admin-toolbar">

            <form method="get" class="toolbar-group">
                <label for="text_id">Tekst</label>
                <select id="text_id" name="text_id" onchange="this.form.submit()">
                    <option value="">Alle tekster</option>
                    <?php foreach ($texts as $x): ?>
                        <option value="<?= (int)$x['id'] ?>" <?= (($_GET['text_id'] ?? '') == $x['id']) ? 'selected' : '' ?>>
                            <?= e((string)$x['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($_GET['status'])): ?>
                    <input type="hidden" name="status" value="<?= e((string)$_GET['status']) ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['type'])): ?>
                    <input type="hidden" name="type" value="<?= e((string)$_GET['type']) ?>">
                <?php endif; ?>
            </form>

            <form method="get" class="toolbar-group">
                <label for="status">Status</label>
                <select id="status" name="status" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <option value="draft" <?= (($_GET['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Utkast</option>
                    <option value="approved" <?= (($_GET['status'] ?? '') === 'approved') ? 'selected' : '' ?>>Godkjent</option>
                    <option value="archived" <?= (($_GET['status'] ?? '') === 'archived') ? 'selected' : '' ?>>Arkivert</option>
                </select>

                <?php if (!empty($_GET['text_id'])): ?>
                    <input type="hidden" name="text_id" value="<?= (int)$_GET['text_id'] ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['type'])): ?>
                    <input type="hidden" name="type" value="<?= e((string)$_GET['type']) ?>">
                <?php endif; ?>
            </form>

            <form method="get" class="toolbar-group">
                <label for="type">Type</label>
                <select id="type" name="type" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <?php foreach ($taskTypeMap as $code => $label): ?>
                        <option value="<?= e((string)$code) ?>" <?= (($_GET['type'] ?? '') === (string)$code) ? 'selected' : '' ?>>
                            <?= e((string)$label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if (!empty($_GET['text_id'])): ?>
                    <input type="hidden" name="text_id" value="<?= (int)$_GET['text_id'] ?>">
                <?php endif; ?>
                <?php if (!empty($_GET['status'])): ?>
                    <input type="hidden" name="status" value="<?= e((string)$_GET['status']) ?>">
                <?php endif; ?>
            </form>

            <div class="toolbar-group toolbar-group--meta">
                <label>Viser</label>
                <div class="toolbar-meta">
                    <span class="toolbar-meta__value"><?= count($tasks) ?></span>
                    <span>oppgaver</span>
                </div>
            </div>

        </div>

        <?php if (has_any_filter()): ?>
            <div style="margin-top:12px;">
                <a href="tasks.php" class="hn-btn secondary">Nullstill filter</a>
            </div>
        <?php endif; ?>

    </div>

    <?php if (!$tasks): ?>
        <div class="admin-card" style="margin-top:16px;">
            Ingen oppgaver funnet.
        </div>
    <?php else: ?>

        <form method="post" id="bulkForm">

            <div class="admin-toolbar-card" style="margin-top:16px;">
                <div class="admin-toolbar" style="grid-template-columns: 320px auto;">
                    <div class="toolbar-group">
                        <label for="bulk_action">Bulk-handling</label>
                        <select id="bulk_action" name="bulk_action">
                            <option value="">Velg handling</option>
                            <option value="approve">Godkjenn valgte</option>
                            <option value="archive">Arkiver valgte</option>
                            <option value="delete">Slett valgte</option>
                        </select>
                    </div>

                    <div class="toolbar-group toolbar-group--meta">
                        <label>&nbsp;</label>
                        <button type="submit" class="hn-btn" style="height:44px;">Utfør</button>
                    </div>
                </div>
            </div>

            <div class="admin-table-wrap" style="margin-top:16px;">
                <table class="admin-table admin-table--dense" id="taskTable">

                    <colgroup>
                        <col style="width:44px;">
                        <col style="width:260px;">
                        <col style="width:160px;">
                        <col style="width:auto;">
                        <col style="width:140px;">
                        <col style="width:320px;">
                        <col style="width:190px;">
                    </colgroup>

                    <thead>
                    <tr>
                        <th style="width:44px;text-align:center;">
                            <input type="checkbox" id="check-all" aria-label="Velg alle">
                        </th>
                        <th>Tekst</th>
                        <th>Type</th>
                        <th>Oppgave</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Handlinger</th>
                        <th>Opprettet</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($tasks as $task):
                        $payload = json_decode((string)$task['payload_json'], true) ?: [];
                        $type    = (string)$task['task_type'];
                        $preview = preview_text($type, $payload);
                        $status  = (string)$task['status'];
                    ?>
                        <tr>
                            <td style="text-align:center;">
                                <input type="checkbox"
                                       name="task_ids[]"
                                       value="<?= (int)$task['id'] ?>"
                                       class="row-check">
                            </td>

                            <td>
                                <div class="text-title"><?= e((string)$task['text_title']) ?></div>
                                <div class="text-meta">
                                    <span class="mono">Task #<?= (int)$task['id'] ?></span>
                                </div>
                            </td>

                            <td>
                                <div class="text-title"><?= e(type_label($type, $taskTypeMap)) ?></div>
                                <div class="text-meta mono"><?= e($type) ?></div>
                            </td>

                            <td>
                                <a href="tasks_edit.php?id=<?= (int)$task['id'] ?>" style="font-weight:700;">
                                    <?= e((string)$preview) ?>
                                </a>
                            </td>

                            <td style="text-align:center;">
                                <span class="badge <?= e(badge_class($status)) ?>">
                                    <?= e($status) ?>
                                </span>
                            </td>

                            <td class="actions-col" style="justify-content:flex-end;">

                                <form method="post" style="display:inline-flex; gap:10px; align-items:center; margin:0;">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <select name="new_status" style="height:38px;">
                                        <option value="draft" <?= $status==='draft'?'selected':'' ?>>Utkast</option>
                                        <option value="approved" <?= $status==='approved'?'selected':'' ?>>Godkjent</option>
                                        <option value="archived" <?= $status==='archived'?'selected':'' ?>>Arkivert</option>
                                    </select>
                                    <button type="submit" class="hn-btn small-btn">Lagre</button>
                                </form>

                                <form method="post"
                                      action="tasks_delete.php"
                                      style="margin:0;"
                                      onsubmit="return confirm('Er du sikker på at du vil slette denne oppgaven?');">
                                    <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                                    <button type="submit"
                                            class="hn-btn small-btn secondary"
                                            style="background:#fee2e2; color:#991b1b;">
                                        Slett
                                    </button>
                                </form>

                            </td>

                            <td>
                                <div class="text-meta"><?= e((string)$task['created_at']) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>

        </form>

    <?php endif; ?>

</section>

</main>

<script src="../js/admin-tasks.js"></script>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>