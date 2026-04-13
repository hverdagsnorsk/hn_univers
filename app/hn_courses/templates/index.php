<?php require HN_CORE . '/layout/header.php'; ?>

<h1>Kurs</h1>

<section class="course-list">

    <?php if (empty($courses)): ?>
        <p>Ingen kurs tilgjengelig.</p>
    <?php else: ?>

        <ul class="course-items">
            <?php foreach ($courses as $course): ?>
                <li class="course-item">

                    <a class="course-link"
                       href="/hn_courses/course/<?= htmlspecialchars($course['slug']) ?>">

                        <span class="course-title">
                            <?= htmlspecialchars($course['title']) ?>
                        </span>

                    </a>

                </li>
            <?php endforeach; ?>
        </ul>

    <?php endif; ?>

</section>

<?php require HN_CORE . '/layout/footer.php'; ?>