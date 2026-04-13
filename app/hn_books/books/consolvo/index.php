<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../engine/bootstrap.php';

$BOOK_KEY = 'consolvo';

$stmt = $pdo->prepare("
SELECT
    t.id,
    t.text_key,
    t.title,
    t.source_path,

    s.id AS task_set_id,

    (
        SELECT COUNT(*)
        FROM task_set_items i
        WHERE i.task_set_id = s.id
    ) AS task_count

FROM texts t

LEFT JOIN task_sets s
ON s.id = (
    SELECT s2.id
    FROM task_sets s2
    WHERE s2.text_id = t.id
    ORDER BY s2.id DESC
    LIMIT 1
)

WHERE t.book_key = :book
AND t.active = 1

ORDER BY t.text_key ASC
");

$stmt->execute(['book' => $BOOK_KEY]);

$texts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Norsk for Consolvo – læring og praksis</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="css/style.css">
</head>

<body>
<div class="page">

<header class="hero hero-cover">
    <div class="hero-overlay">
        <h1>Norsk for Consolvo</h1>
        <p class="hero-sub">Digital bok med tekster og oppgaver</p>
    </div>
</header>

<main class="content">
<section class="toc">

<?php if (!$texts): ?>
    <p>Ingen tekster funnet.</p>
<?php endif; ?>

<?php foreach ($texts as $t): ?>

<?php
$nr = null;
if (preg_match('/-(\d{3})$/', (string)$t['text_key'], $m)) {
    $nr = (int)$m[1];
}
?>

<article class="toc-card">

    <div class="toc-meta">
        <?php if ($nr !== null): ?>
            <span class="toc-label">Tekst <?= $nr ?></span>
        <?php endif; ?>
    </div>

    <h3><?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?></h3>

    <div class="toc-actions">

        <a class="btn primary"
           href="<?= htmlspecialchars($t['source_path'], ENT_QUOTES, 'UTF-8') ?>">
           📘 Les tekst
        </a>

        <?php if (!empty($t['task_set_id']) && (int)$t['task_count'] > 0): ?>
            <a class="btn secondary"
               href="/hn_books/engine/tasks_play.php?set_id=<?= (int)$t['task_set_id'] ?>">
               📝 Oppgaver (<?= (int)$t['task_count'] ?>)
            </a>
        <?php endif; ?>

        <?php
        $qrPath = "/hn_books/qr/text_{$t['id']}.png";
        if (is_file($_SERVER['DOCUMENT_ROOT'] . $qrPath)):
        ?>
            <div class="qr-wrapper">
                <img
                    src="<?= $qrPath ?>"
                    class="qr-small"
                    alt="QR-kode for ordinnlæring">
                <div class="qr-label">Ordinnlæring</div>
            </div>
        <?php endif; ?>

    </div>
</article>

<?php endforeach; ?>

</section>
</main>

<footer>© 2026 Hverdagsnorsk</footer>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.qr-small').forEach(qr => {

        qr.addEventListener('click', e => {
            e.stopPropagation();

            document.querySelectorAll('.qr-small.is-zoomed')
                .forEach(open => {
                    if (open !== qr) {
                        open.classList.remove('is-zoomed');
                    }
                });

            qr.classList.toggle('is-zoomed');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.qr-small.is-zoomed')
            .forEach(qr => qr.classList.remove('is-zoomed'));
    });

});
</script>

</body>
</html>