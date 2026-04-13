<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/bootstrap.php';

/* ==========================================================
   SIDEKONFIGURASJON
========================================================== */

/* ==========================================================
   HJELPER
========================================================== */

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* ==========================================================
   1. Mest klikkede ord (FINNES I LEKSIKON)
========================================================== */

$popularStmt = $pdo->query("
    SELECT
        e.lemma,
        c.language,
        COUNT(*) AS clicks
    FROM lex_clicks c
    INNER JOIN lex_entries e 
        ON e.id = c.entry_id
    WHERE c.found = 1
      AND c.entry_id IS NOT NULL
    GROUP BY c.entry_id, c.language
    ORDER BY clicks DESC
    LIMIT 30
");

$popular = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   2. Mest klikkede ord som mangler
========================================================== */

$missingStmt = $pdo->query("
    SELECT
        word,
        language,
        COUNT(*) AS clicks
    FROM lex_clicks
    WHERE found = 0
    GROUP BY word, language
    ORDER BY clicks DESC
    LIMIT 30
");

$missing = $missingStmt->fetchAll(PDO::FETCH_ASSOC);

/* ==========================================================
   3. Mest aktive sider (HN Books analyse)
========================================================== */

$pageStmt = $pdo->query("
    SELECT
        page,
        COUNT(*) AS clicks
    FROM lex_clicks
    WHERE page IS NOT NULL
    GROUP BY page
    ORDER BY clicks DESC
    LIMIT 30
");

$pages = $pageStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Leksikon – bruk og statistikk</h2>

<p class="hn-meta">
    Data hentet fra faktiske oppslag i HN Books.
</p>

<!-- ===================================================== -->
<!-- MEST KLIKKEDE ORD (FINNES) -->
<!-- ===================================================== -->

<section class="hn-card">
    <h3>Mest klikkede ord (finnes i leksikon)</h3>

    <?php if (!$popular): ?>
        <p><em>Ingen data.</em></p>
    <?php else: ?>
        <table class="hn-table">
            <thead>
                <tr>
                    <th>Lemma</th>
                    <th>Språk</th>
                    <th>Klikk</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($popular as $r): ?>
                <tr>
                    <td><?= h($r['lemma']) ?></td>
                    <td><?= h($r['language']) ?></td>
                    <td><?= (int)$r['clicks'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<!-- ===================================================== -->
<!-- MEST KLIKKEDE MANGLENDE ORD -->
<!-- ===================================================== -->

<section class="hn-card">
    <h3>Mest klikkede ord som mangler</h3>

    <?php if (!$missing): ?>
        <p><em>Ingen manglende ord.</em></p>
    <?php else: ?>
        <table class="hn-table">
            <thead>
                <tr>
                    <th>Ord</th>
                    <th>Språk</th>
                    <th>Klikk</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($missing as $r): ?>
                <tr>
                    <td><?= h($r['word']) ?></td>
                    <td><?= h($r['language']) ?></td>
                    <td><?= (int)$r['clicks'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<!-- ===================================================== -->
<!-- MEST AKTIVE SIDER -->
<!-- ===================================================== -->

<section class="hn-card">
    <h3>Mest aktive sider (HN Books)</h3>

    <?php if (!$pages): ?>
        <p><em>Ingen data.</em></p>
    <?php else: ?>
        <table class="hn-table">
            <thead>
                <tr>
                    <th>Side</th>
                    <th>Klikk</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pages as $r): ?>
                <tr>
                    <td><?= h($r['page']) ?></td>
                    <td><?= (int)$r['clicks'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php  ?>
