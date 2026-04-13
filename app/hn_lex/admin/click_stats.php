<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

/* ==========================================================
   TOP WORDS (GLOBAL)
========================================================== */

$topWords = $pdo_lex->query("
    SELECT word, COUNT(*) AS total
    FROM lex_clicks
    GROUP BY word
    ORDER BY total DESC
    LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   CLICKS PER DAY
========================================================== */

$clicksPerDay = $pdo_lex->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS total
    FROM lex_clicks
    GROUP BY DATE(created_at)
    ORDER BY day DESC
    LIMIT 14
")->fetchAll(PDO::FETCH_ASSOC);

?>

<h2>Click Analytics</h2>

<h3>Topp ord totalt</h3>
<table class="hn-table">
<tr><th>Ord</th><th>Klikk</th></tr>
<?php foreach ($topWords as $row): ?>
<tr>
    <td><?= h($row['word']) ?></td>
    <td><?= (int)$row['total'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3 style="margin-top:40px;">Klikk per dag (siste 14 dager)</h3>
<table class="hn-table">
<tr><th>Dato</th><th>Klikk</th></tr>
<?php foreach ($clicksPerDay as $row): ?>
<tr>
    <td><?= h($row['day']) ?></td>
    <td><?= (int)$row['total'] ?></td>
</tr>
<?php endforeach; ?>
</table>