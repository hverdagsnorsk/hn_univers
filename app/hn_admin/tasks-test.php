<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| hn_admin/tasks.php
| Admin – Oppgaver
| - Støtter nye oppgavetyper (mcq, short, fill, match, writing)
| - Forbedret forhåndsvisning (inkl. match=pairs)
| - UI i samme visuelle språk som dashboard
|--------------------------------------------------------------------------*/

require_once __DIR__ . '/bootstrap.php';

/* --------------------------------------------------
   BULK handlinger
-------------------------------------------------- */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['bulk_action'])
    && !empty($_POST['task_ids'])
    && is_array($_POST['task_ids'])
) {
    $ids = array_values(array_filter(array_map('intval', $_POST['task_ids']), fn($v) => $v > 0));
    if ($ids) {
        $in  = implode(',', array_fill(0, count($ids), '?'));

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
            'id'     => $tid
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
   (Valgfritt) Hent task_types hvis tabellen finnes
   - Fallback til hardkodet mapping
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
    // Standardiserer slik at UI aldri blir tom ved nye varianter
    $prompt = (string)($payload['prompt'] ?? '');

    if ($type === 'mcq' || $type === 'short' || $type === 'writing') {
        return $prompt !== '' ? $prompt : '—';
    }

    if ($type === 'fill') {
        $s = (string)($payload['sentence'] ?? '');
        return $s !== '' ? $s : '—';
    }

    if ($type === 'match') {
        // Ny struktur i generator: payload.pairs
        if (isset($payload['pairs']) && is_array($payload['pairs'])) {
            $n = count($payload['pairs']);
            return $n > 0 ? ('Koble sammen (' . $n . ' par)') : 'Koble sammen (0 par)';
        }
        // Bakoverkompatibilitet hvis noen gamle har items
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
        'approved' => 'status-approved',
        'draft'    => 'status-draft',
        'archived' => 'status-archived',
        default    => 'status-archived',
    };
}

// Enkle URL-param hjelpere
function q(array $extra = []): string {
    $merged = array_merge($_GET, $extra);
    foreach ($merged as $k => $v) {
        if ($v === '' || $v === null) unset($merged[$k]);
    }
    return http_build_query($merged);
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Oppgaver</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="admin-test.css">
</head>

<body>
<div class="container">

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Oppgaver</h1>
        <div class="sub">AI-genererte og redigerte oppgaver • 2026</div>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<div class="topbar">
    <div class="filters">
        <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
            <label>
                Tekst
                <select name="text_id" onchange="this.form.submit()">
                    <option value="">Alle tekster</option>
                    <?php foreach ($texts as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (($_GET['text_id'] ?? '') == $t['id']) ? 'selected' : '' ?>>
                            <?= e((string)$t['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                Status
                <select name="status" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <option value="draft" <?= (($_GET['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Utkast</option>
                    <option value="approved" <?= (($_GET['status'] ?? '') === 'approved') ? 'selected' : '' ?>>Godkjent</option>
                    <option value="archived" <?= (($_GET['status'] ?? '') === 'archived') ? 'selected' : '' ?>>Arkivert</option>
                </select>
            </label>

            <label>
                Type
                <select name="type" onchange="this.form.submit()">
                    <option value="">Alle</option>
                    <?php foreach ($taskTypeMap as $code => $label): ?>
                        <option value="<?= e((string)$code) ?>" <?= (($_GET['type'] ?? '') === (string)$code) ? 'selected' : '' ?>>
                            <?= e((string)$label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <?php if (!empty($_GET['text_id']) || !empty($_GET['status']) || !empty($_GET['type'])): ?>
                <a href="tasks.php" style="font-weight:900">Nullstill filter</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($tasks): ?>
<form method="post" onsubmit="return confirmBulk();">

<div class="card">
    <div class="bulk-actions">
        <select name="bulk_action">
            <option value="">Velg handling</option>
            <option value="approve">Godkjenn valgte</option>
            <option value="archive">Arkiver valgte</option>
            <option value="delete">Slett valgte</option>
        </select>
        <button type="submit">Utfør</button>
        <div class="count">
    Antall: <?= count($tasks) ?>
</div>    </div>
</div>

<table>
<thead>
<tr>
    <th style="width:38px"><input type="checkbox" id="check-all"></th>
    <th>Tekst</th>
    <th style="width:150px">Type</th>
    <th>Oppgave</th>
    <th style="width:120px">Status</th>
    <th style="width:260px">Handlinger</th>
    <th style="width:170px">Opprettet</th>
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
    <td>
        <input type="checkbox"
               name="task_ids[]"
               value="<?= (int)$task['id'] ?>"
               class="row-check">
    </td>

    <td><strong><?= e((string)$task['text_title']) ?></strong></td>

    <td><?= e(type_label($type, $taskTypeMap)) ?><br><small><?= e($type) ?></small></td>

    <td>
        <a href="tasks_edit.php?id=<?= (int)$task['id'] ?>">
            <?= e((string)$preview) ?>
        </a><br>
        <small>ID: <?= (int)$task['id'] ?></small>
    </td>

    <td>
        <span class="badge <?= e(badge_class($status)) ?>">
            <?= e($status) ?>
        </span>
    </td>

    <td>
        <div class="actions">
            <form method="post" style="display:inline-flex;gap:8px;align-items:center;margin:0">
                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                <select name="new_status">
                    <option value="draft" <?= $status==='draft'?'selected':'' ?>>Utkast</option>
                    <option value="approved" <?= $status==='approved'?'selected':'' ?>>Godkjent</option>
                    <option value="archived" <?= $status==='archived'?'selected':'' ?>>Arkivert</option>
                </select>
                <button type="submit">Lagre</button>
            </form>

            <form method="post" action="tasks_delete.php"
                  style="margin:0"
                  onsubmit="return confirm('Er du sikker på at du vil slette denne oppgaven?');">
                <input type="hidden" name="task_id" value="<?= (int)$task['id'] ?>">
                <button type="submit" class="delete">Slett</button>
            </form>
        </div>
    </td>

    <td><small><?= e((string)$task['created_at']) ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</form>
<?php else: ?>
<div class="card">
    Ingen oppgaver funnet.
</div>
<?php endif; ?>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</div>

<script>
document.getElementById('check-all')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.checked = this.checked;
    });
});

function confirmBulk() {
    const action = document.querySelector('[name="bulk_action"]').value;
    const checked = document.querySelectorAll('.row-check:checked').length;

    if (!action) {
        alert('Velg en handling først.');
        return false;
    }
    if (checked === 0) {
        alert('Du må velge minst én oppgave.');
        return false;
    }
    if (action === 'delete') {
        return confirm('Er du sikker på at du vil slette valgte oppgaver?');
    }
    return true;
}
</script>

</body>
</html>
