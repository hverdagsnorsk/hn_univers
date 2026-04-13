<?php
declare(strict_types=1);

use HnCourses\Repository\CourseRepository;
use function HnCourses\Support\course_db;

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$courseRepository = new CourseRepository(course_db());
$courses = $courseRepository->findAll();

$page_title = 'Kursmodul';
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';
?>

<main class="page admin-page">

    <header class="page-header">
        <h1>Kursmodul</h1>
        <p class="page-intro">
            Oversikt over kurssidene og snarveier til administrasjon.
        </p>
    </header>

    <section class="admin-card-section">
        <h2>Snarveier</h2>

        <div class="admin-course-grid">

            <div class="admin-course-card">
                <h3>Kurssider</h3>
                <p>Åpne den offentlige oversikten over alle aktive kurs.</p>
                <a href="/hn_courses/public/index.php" class="hn-btn">
                    Åpne kurssider
                </a>
            </div>

            <div class="admin-course-card">
                <h3>Kursadmin</h3>
                <p>Gå til administrasjon av kursinnhold, ressurser og struktur.</p>
                <a href="/hn_courses/admin/public/index.php" class="hn-btn">
                    Åpne admin
                </a>
            </div>

        </div>
    </section>

    <section class="admin-card-section">
        <h2>Alle kurs</h2>

        <?php if (empty($courses)): ?>
            <div class="admin-course-card">
                <h3>Ingen kurs funnet</h3>
                <p>Det finnes ingen kurs i databasen ennå.</p>
            </div>
        <?php else: ?>
            <div class="admin-course-grid">
                <?php foreach ($courses as $course): ?>
                    <?php
                    $courseId = (int) ($course['id'] ?? 0);
                    $title = trim((string) ($course['title'] ?? 'Uten tittel'));
                    $description = trim((string) ($course['short_description'] ?? ''));
                    $slug = trim((string) ($course['slug'] ?? ''));
                    $isActive = (int) ($course['is_active'] ?? 0) === 1;
                    ?>

                    <article class="admin-course-card">
                        <h3><?= htmlspecialchars($title) ?></h3>

                        <?php if ($description !== ''): ?>
                            <p><?= nl2br(htmlspecialchars($description)) ?></p>
                        <?php else: ?>
                            <p>Ingen kort beskrivelse lagt inn.</p>
                        <?php endif; ?>

                        <p>
                            <strong>Status:</strong>
                            <?= $isActive ? 'Aktiv' : 'Inaktiv' ?>
                        </p>

                        <div style="display:flex; gap:10px; flex-wrap:wrap;">
                            <?php if ($slug !== ''): ?>
                                <a href="/hn_courses/public/course.php?slug=<?= urlencode($slug) ?>" class="hn-btn">
                                    Vis
                                </a>
                            <?php else: ?>
                                <a href="/hn_courses/public/course.php?id=<?= $courseId ?>" class="hn-btn">
                                    Vis
                                </a>
                            <?php endif; ?>

                            <a href="/hn_courses/admin/public/index.php?course_id=<?= $courseId ?>" class="hn-btn">
                                Rediger
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>