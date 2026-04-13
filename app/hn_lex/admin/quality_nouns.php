<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';
require_once $root . '/hn_lex/inc/LexTerminology.php';

/* ==========================================================
   DATABASE
========================================================== */

$pdo_lex = db('lex');

$page_title  = "Lex – Kvalitetskontroll (Substantiv)";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';

/* ==========================================================
   HENT SUBSTANTIV
========================================================== */

$sql = "
SELECT 
    e.id,
    e.lemma,
    n.gender,
    n.gender_alt,
    n.singular_definite,
    n.singular_definite_alt
FROM lex_entries e
JOIN lex_word_classes wc ON wc.id = e.word_class_id
JOIN lex_nouns n ON n.entry_id = e.id
WHERE wc.code = 'substantiv'
ORDER BY e.id DESC
";

$stmt = $pdo_lex->query($sql);

if (!$stmt) {
    echo "<p>Databasefeil ved henting av substantiv.</p>";
    require_once $root . '/hn_core/layout/footer.php';
    exit;
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   VALIDERING
========================================================== */

function suspicious(array $row): bool
{
    $gender = $row['gender'] ?? null;
    $def    = (string)($row['singular_definite'] ?? '');

    if (!$gender) {
        return true;
    }

    // n-ord som ender på -en
    if ($gender === 'n' && str_ends_with($def, 'en')) {
        return true;
    }

    // m/f-ord som ender på -et
    if (
        ($gender === 'm' || $gender === 'f') &&
        str_ends_with($def, 'et')
    ) {
        return true;
    }

    return false;
}

$filtered = array_filter($rows, fn($r) => suspicious($r));
?>

<style>
.qa-table tr:hover {
    background:#f8fafc;
}

.qa-badge {
    padding:4px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
    background:#fef3c7;
    color:#92400e;
}

.qa-meta {
    margin-bottom:20px;
    font-size:14px;
    color:#6b7280;
}
</style>

<main class="page admin-page">

<header class="page-header">
    <h1>Kvalitetskontroll – Substantiv</h1>
    <p class="page-intro">
        Automatisk flagging av mulig inkonsistente kjønn vs bøyning
    </p>
</header>

<section class="admin-card-section">

<p class="qa-meta">
    Totalt sjekket: <?= count($rows) ?> |
    Mistenkelige funn:
    <span class="qa-badge"><?= count($filtered) ?></span>
</p>

<table class="hn-table qa-table">
<thead>
<tr>
    <th>ID</th>
    <th>Lemma</th>
    <th>Kjønn</th>
    <th>Bestemt entall</th>
    <th>Handling</th>
</tr>
</thead>
<tbody>

<?php foreach ($filtered as $r): ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= htmlspecialchars($r['lemma']) ?></td>
    <td>
        <?= htmlspecialchars((string)$r['gender']) ?>
        <?php if (!empty($r['gender_alt'])): ?>
            / <?= htmlspecialchars((string)$r['gender_alt']) ?>
        <?php endif; ?>
    </td>
    <td>
        <?= htmlspecialchars((string)$r['singular_definite']) ?>
        <?php if (!empty($r['singular_definite_alt'])): ?>
            / <?= htmlspecialchars((string)$r['singular_definite_alt']) ?>
        <?php endif; ?>
    </td>
    <td>
        <a class="hn-btn hn-btn-small"
           href="entry_editor.php?id=<?= (int)$r['id'] ?>">
           Rediger
        </a>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

</section>
</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>