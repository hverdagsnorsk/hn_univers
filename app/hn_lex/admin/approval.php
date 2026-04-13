<?php
declare(strict_types=1);

/* ==========================================================
   BOOTSTRAP
========================================================== */

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

/* ==========================================================
   DB
========================================================== */

$pdo = DatabaseManager::get('lex');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==========================================================
   LOAD STORAGE SERVICE
========================================================== */

require_once __DIR__ . '/../src/Service/LexStorageService.php';

$storage = new LexStorageService($pdo);

/* ==========================================================
   ACTION HANDLING
========================================================== */

$action = $_POST['action'] ?? null;
$id     = isset($_POST['id']) ? (int)$_POST['id'] : null;

$message = null;
$error   = null;

if ($action && $id) {

    try {

        /* ==================================================
           FETCH STAGING
        ================================================== */

        $stmt = $pdo->prepare("
            SELECT *
            FROM lex_entries_staging
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$entry) {
            throw new RuntimeException("Entry not found");
        }

        /* ==================================================
           APPROVE
        ================================================== */

        if ($action === 'approve') {

            $data = json_decode($entry['payload_json'], true);

            if (!$data) {
                throw new RuntimeException("Invalid JSON payload");
            }

            /* ---------- store ---------- */

            $entryId = $storage->store($data);

            /* ---------- update staging ---------- */

            $stmt = $pdo->prepare("
                UPDATE lex_entries_staging
                SET status = 'approved',
                    approved_at = NOW(),
                    approved_by = 'system'
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = "Approved → entry_id {$entryId}";
        }

        /* ==================================================
           REJECT
        ================================================== */

        if ($action === 'reject') {

            $stmt = $pdo->prepare("
                UPDATE lex_entries_staging
                SET status = 'rejected'
                WHERE id = ?
            ");

            $stmt->execute([$id]);

            $message = "Entry rejected";
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

/* ==========================================================
   FETCH PENDING
========================================================== */

$stmt = $pdo->query("
    SELECT id, lemma, word_class, created_at
    FROM lex_entries_staging
    WHERE status = 'pending'
    ORDER BY created_at ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="utf-8">
<title>Lex Approval</title>
<style>
body { font-family: system-ui; margin: 20px; }
table { border-collapse: collapse; width: 100%; }
td, th { padding: 8px; border-bottom: 1px solid #ddd; }
button { padding: 6px 10px; margin-right: 5px; }
.msg { color: green; }
.err { color: red; }
</style>
</head>
<body>

<h1>Lex Approval</h1>

<?php if ($message): ?>
    <p class="msg"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<?php if ($error): ?>
    <p class="err"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Lemma</th>
            <th>Ordklasse</th>
            <th>Opprettet</th>
            <th>Handling</th>
        </tr>
    </thead>
    <tbody>

    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['lemma']) ?></td>
            <td><?= htmlspecialchars($r['word_class']) ?></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit">Approve</button>
                </form>

                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit">Reject</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>

    </tbody>
</table>

</body>
</html>