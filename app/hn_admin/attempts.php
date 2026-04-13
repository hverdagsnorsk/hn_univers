<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| hn_admin/attempts.php
| Læreroversikt – innleveringer
|------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

if (!isset($pdo) || !$pdo instanceof PDO) {
    http_response_code(500);
    exit('PDO mangler.');
}

/* --------------------------------------------------
   Filtre
-------------------------------------------------- */
$email  = trim($_GET['email']  ?? '');
$textId = (int)($_GET['text']  ?? 0);
$status = trim($_GET['status'] ?? '');
$order  = $_GET['order'] ?? 'newest';

$where  = [];
$params = [];

if ($email !== '') {
    $where[] = 'a.participant_email = :email';
    $params['email'] = $email;
}

if ($textId > 0) {
    $where[] = 'a.text_id = :text_id';
    $params['text_id'] = $textId;
}

if ($status === 'new') {
    $where[] = 'a.teacher_comment IS NULL';
}
if ($status === 'reviewed') {
    $where[] = 'a.teacher_comment IS NOT NULL';
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderSql = match ($order) {
    'oldest' => 'COALESCE(a.finished_at, a.started_at) ASC',
    default  => 'COALESCE(a.finished_at, a.started_at) DESC',
};

/* --------------------------------------------------
   Hent innleveringer
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT
        a.id,
        a.text_id,
        a.participant_name,
        a.participant_email,
        a.started_at,
        a.finished_at,
        a.teacher_comment,
        t.title,
        JSON_LENGTH(a.answers_json) AS task_count
    FROM attempts a
    JOIN texts t ON t.id = a.text_id
    $sqlWhere
    ORDER BY $orderSql
");
$stmt->execute($params);
$attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* --------------------------------------------------
   Tekster (filter)
-------------------------------------------------- */
$texts = db()->query("
    SELECT id, title
    FROM texts
    ORDER BY title
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Innleveringer – administrasjon</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    font-family: Segoe UI, system-ui, sans-serif;
    background:#f4f6f6;
    padding:30px;
}

.header{
    display:flex;
    align-items:center;
    gap:20px;
    margin-bottom:30px;
}

.header img{ max-height:56px; }

h1{ margin:0; color:#2f8485; }

a{
    color:#2f8485;
    text-decoration:none;
    font-weight:600;
}

.filter-box{
    background:#ffffff;
    padding:18px 22px;
    border-radius:14px;
    box-shadow:0 6px 18px rgba(0,0,0,.06);
    margin-bottom:24px;
}

.filter-box form{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}

input, select, button{
    padding:10px 12px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    font-weight:600;
}

button{
    background:#2f8485;
    color:#ffffff;
    cursor:pointer;
}

table{
    width:100%;
    border-collapse:collapse;
    background:#ffffff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 8px 22px rgba(0,0,0,.08);
}

th, td{
    padding:12px 14px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:top;
}

th{
    background:#2f8485;
    color:#ffffff;
    font-weight:700;
    font-size:.95rem;
}

tr:hover{ background:#f1f9f9; }

.badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:700;
}

.badge-new{
    background:#fee2e2;
    color:#991b1b;
}

.badge-reviewed{
    background:#d1fae5;
    color:#065f46;
}

.footer{
    text-align:center;
    margin-top:50px;
    font-size:.85rem;
    color:#64748b;
}
</style>
</head>

<body>

<div class="header">
    <img src="../images/logo_transparent.png" alt="Hverdagsnorsk logo">
    <div>
        <h1>Innleveringer</h1>
        <a href="index.php">← Tilbake til adminpanel</a>
    </div>
</div>

<div class="filter-box">
<form method="get">

<input type="text"
       name="email"
       placeholder="E-post"
       value="<?= e($email) ?>">

<select name="text">
  <option value="">Alle tekster</option>
  <?php foreach ($texts as $t): ?>
    <option value="<?= (int)$t['id'] ?>" <?= $textId === (int)$t['id'] ? 'selected' : '' ?>>
      <?= e($t['title']) ?>
    </option>
  <?php endforeach; ?>
</select>

<select name="status">
  <option value="">Alle</option>
  <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Ny</option>
  <option value="reviewed" <?= $status === 'reviewed' ? 'selected' : '' ?>>Kommentert</option>
</select>

<select name="order">
  <option value="newest" <?= $order === 'newest' ? 'selected' : '' ?>>Nyeste først</option>
  <option value="oldest" <?= $order === 'oldest' ? 'selected' : '' ?>>Eldste først</option>
</select>

<button>Filtrer</button>

</form>
</div>

<table>
<thead>
<tr>
  <th>Dato</th>
  <th>Deltaker</th>
  <th>Tekst</th>
  <th>Oppgaver</th>
  <th>Status</th>
  <th></th>
</tr>
</thead>
<tbody>

<?php if (!$attempts): ?>
<tr>
  <td colspan="6"><em>Ingen innleveringer funnet.</em></td>
</tr>
<?php endif; ?>

<?php foreach ($attempts as $a): ?>
<tr>
  <td>
    <?php
      $dt = $a['finished_at'] ?: $a['started_at'];
      echo $dt ? e(date('Y-m-d H:i', strtotime($dt))) : '<em>Pågår</em>';
    ?>
  </td>

  <td>
    <strong><?= e($a['participant_name']) ?></strong><br>
    <a href="participant.php?email=<?= urlencode($a['participant_email']) ?>">
      <?= e($a['participant_email']) ?>
    </a>
  </td>

  <td><?= e($a['title']) ?></td>

  <td><?= (int)$a['task_count'] ?></td>

  <td>
    <?= $a['teacher_comment']
      ? '<span class="badge badge-reviewed">Kommentert</span>'
      : '<span class="badge badge-new">Ny</span>' ?>
  </td>

  <td>
    <a href="attempt_view.php?id=<?= (int)$a['id'] ?>">Se besvarelse</a>
  </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<div class="footer">
© 2026 Hverdagsnorsk. Alle rettigheter forbeholdt.
</div>

</body>
</html>
