<?php require HN_CORE . '/layout/header.php'; ?>

<section class="class-page">

    <header class="class-header">
        <h1><?= htmlspecialchars($course['title']) ?></h1>
        <p class="class-subtitle">Kursøkt</p>
    </header>

    <section class="class-details">

        <div class="class-row">
            <strong>Dato:</strong>
            <span><?= htmlspecialchars($class['start_datetime'] ?? 'Ikke angitt') ?></span>
        </div>

        <?php if (!empty($class['location'])): ?>
            <div class="class-row">
                <strong>Sted:</strong>
                <span><?= htmlspecialchars($class['location']) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($class['description'])): ?>
            <div class="class-description">
                <?= nl2br(htmlspecialchars($class['description'])) ?>
            </div>
        <?php endif; ?>

    </section>

    <hr>

    <section class="class-navigation">
        <a class="back-link"
           href="/hn_courses/course/<?= htmlspecialchars($course['slug']) ?>">
            ← Tilbake til kurs
        </a>
    </section>

</section>

<?php require HN_CORE . '/layout/footer.php'; ?>