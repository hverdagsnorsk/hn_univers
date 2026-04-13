<?php
// lex_nouns editor with DELETE + AJAX + safe handling

declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';
require_once __DIR__ . '/../../hn_core/auth/admin.php';

$pdo = db();

/* ==========================
   AJAX SAVE (OK)
========================== */
if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '') === 'save') {
    header('Content-Type: application/json');

    $row = $_POST['row'] ?? [];
    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['ok'=>false]);
        exit;
    }

    $stmt = db()->prepare("UPDATE lex_nouns SET
        gender=:gender,
        gender_alt=:gender_alt,
        countable=:countable,
        singular_indefinite=:si,
        singular_indefinite_alt=:si_alt,
        singular_definite=:sd,
        singular_definite_alt=:sd_alt,
        plural_indefinite=:pi,
        plural_indefinite_alt=:pi_alt,
        plural_definite=:pd,
        plural_definite_alt=:pd_alt,
        reviewed=1
        WHERE entry_id=:id
    ");

    $stmt->execute([
        'gender'=>$row['gender']??'',
        'gender_alt'=>$row['gender_alt']??'',
        'countable'=>$row['countable']??1,
        'si'=>$row['si']??'',
        'si_alt'=>$row['si_alt']??'',
        'sd'=>$row['sd']??'',
        'sd_alt'=>$row['sd_alt']??'',
        'pi'=>$row['pi']??'',
        'pi_alt'=>$row['pi_alt']??'',
        'pd'=>$row['pd']??'',
        'pd_alt'=>$row['pd_alt']??'',
        'id'=>$id
    ]);

    echo json_encode(['ok'=>true]);
    exit;
}

/* ==========================
   AJAX DELETE
========================== */
if (isset($_GET['ajax']) && $_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '') === 'delete') {
    header('Content-Type: application/json');

    $id = (int)($_POST['id'] ?? 0);

    if (!$id) {
        echo json_encode(['ok'=>false]);
        exit;
    }

    $stmt = db()->prepare("DELETE FROM lex_nouns WHERE entry_id = :id LIMIT 1");
    $stmt->execute(['id'=>$id]);

    echo json_encode(['ok'=>true]);
    exit;
}

/* ==========================
   FETCH
========================== */
$search = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM lex_nouns";
$params = [];

if ($search) {
    $sql .= " WHERE singular_indefinite LIKE :q";
    $params['q'] = "%$search%";
}

$sql .= " ORDER BY reviewed ASC, entry_id ASC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>lex_nouns editor</title>
<style>
body{font-family:Arial;padding:20px;font-size:15px}
table{border-collapse:collapse;width:100%}
th,td{border:1px solid #ccc;padding:6px}
th{background:#eee}
input,select{width:100%;padding:6px;box-sizing:border-box}
.ok-cell{text-align:center}
.delete-btn{background:#e5533d;color:#fff;border:none;padding:6px 8px;cursor:pointer;border-radius:4px}
</style>
</head>
<body>

<h1>lex_nouns editor</h1>

<form method="get">
<input name="q" placeholder="Søk..." value="<?= htmlspecialchars($search) ?>">
</form>

<table>
<tr>
<th>ID</th>
<th>Kjønn</th>
<th>SI</th>
<th>SD</th>
<th>PI</th>
<th>PD</th>
<th>OK</th>
<th>Slett</th>
</tr>

<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r['entry_id'] ?></td>
<td>
<select class="gender">
<option value="m" <?= $r['gender']==='m'?'selected':'' ?>>m</option>
<option value="f" <?= $r['gender']==='f'?'selected':'' ?>>f</option>
<option value="n" <?= $r['gender']==='n'?'selected':'' ?>>n</option>
</select>
</td>
<td><input class="si" value="<?= htmlspecialchars($r['singular_indefinite'] ?? '') ?>"></td>
<td><input class="sd" value="<?= htmlspecialchars($r['singular_definite'] ?? '') ?>"></td>
<td><input class="pi" value="<?= htmlspecialchars($r['plural_indefinite'] ?? '') ?>"></td>
<td><input class="pd" value="<?= htmlspecialchars($r['plural_definite'] ?? '') ?>"></td>
<td class="ok-cell"><input type="checkbox"></td>
<td><button class="delete-btn">🗑</button></td>
</tr>
<?php endforeach; ?>

</table>

<script>

// DELETE

document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', async function(e){
        e.preventDefault();

        if(!confirm('Slette denne oppføringen?')) return;

        const tr = this.closest('tr');
        const id = tr.children[0].innerText.trim();

        const formData = new FormData();
        formData.append('action','delete');
        formData.append('id', id);

        const res = await fetch('?ajax=1', {method:'POST', body:formData});
        const json = await res.json();

        if(json.ok){
            tr.remove();
        } else {
            alert('Feil ved sletting');
        }
    });
});

</script>

</body>
</html>
