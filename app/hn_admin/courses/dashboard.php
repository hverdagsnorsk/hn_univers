<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/hn_core/inc/bootstrap.php';

if (!isset(db('courses'))) {
    exit('Courses DB missing.');
}

$courseId = (int)($_GET['course_id'] ?? 0);

if (!$courseId) {
    exit('Missing course.');
}

/* ==========================================================
   LOAD COURSE
========================================================== */

$stmt = db('courses')->prepare("
    SELECT *
    FROM hn_course_courses
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    exit('Course not found.');
}

/* ==========================================================
   LOAD COUNTS
========================================================== */

$docCount = db('courses')->prepare("
    SELECT COUNT(*)
    FROM hn_course_document_assignments
    WHERE course_id = ?
");
$docCount->execute([$courseId]);
$docCount = (int)$docCount->fetchColumn();

$videoCount = db('courses')->prepare("
    SELECT COUNT(*)
    FROM hn_course_videos
    WHERE course_id = ?
");
$videoCount->execute([$courseId]);
$videoCount = (int)$videoCount->fetchColumn();

$page_title = 'Dashboard – ' . $course['title'];

$layout_mode = 'admin';
require_once $root . '/hn_core/layout/header.php';
?>

<main class="page">

<header class="page-header">
    <h1><?= h($course['title']) ?></h1>
    <p>Administrer kursinnhold</p>
</header>

<section class="card-grid">

    <!-- Rediger -->
    <div class="card">
        <h2>Kursinnstillinger</h2>
        <p>Rediger tittel, bok og kurskode.</p>
        <a class="btn-primary"
           href="edit.php?id=<?= $courseId ?>">
           Rediger kurs
        </a>
    </div>

    <!-- Dokumenter -->
    <div class="card">
        <h2>Dokumenter</h2>
        <p><?= $docCount ?> dokument(er) tilknyttet</p>
        <a class="btn-primary"
           href="assign_documents.php?course_id=<?= $courseId ?>">
           Administrer dokumenter
        </a>
    </div>

    <!-- Videoer -->
    <div class="card">
        <h2>Videoer</h2>
        <p><?= $videoCount ?> video(er)</p>
        <a class="btn-primary"
           href="videos.php?course_id=<?= $courseId ?>">
           Administrer videoer
        </a>
    </div>

    <!-- Schedule -->
    <div class="card">
        <h2>Kursplan (ICS)</h2>
        <p>Last opp ny .ics-fil</p>
        <a class="btn-primary"
           href="schedule.php?course_id=<?= $courseId ?>">
           Oppdater schedule
        </a>
    </div>

    <!-- Se kurs -->
    <div class="card">
        <h2>Vis kurs</h2>
        <p>Åpne kursportalen</p>
        <a class="btn-dark"
           href="<?= HN_COURSE_BASE ?>/course.php?course=<?= h($course['slug']) ?>"
           target="_blank">
           Åpne kurs
        </a>
    </div>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
