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
    SELECT id, slug, title
    FROM hn_course_courses
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$courseId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    exit('Course not found.');
}

$slug = $course['slug'];

$dataPath = HN_COURSE_PATH . "/course/{$slug}/data/";

if (!is_dir($dataPath)) {
    mkdir($dataPath, 0755, true);
}

/* ==========================================================
   HANDLE ICS UPLOAD
========================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ics'])) {

    if ($_FILES['ics']['error'] === UPLOAD_ERR_OK) {

        $content = file_get_contents($_FILES['ics']['tmp_name']);

        if ($content === false) {
            exit('Failed to read ICS.');
        }

        $events = parseIcs($content);

        file_put_contents(
            $dataPath . 'schedule.json',
            json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        file_put_contents(
            $dataPath . 'next_session.json',
            json_encode(array_slice($events, 0, 1), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        header("Location: schedule.php?course_id=" . $courseId);
        exit;
    }
}

/* ==========================================================
   ICS PARSER
========================================================== */

function parseIcs(string $ics): array
{
    $lines = explode("\n", $ics);
    $events = [];
    $current = [];

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === 'BEGIN:VEVENT') {
            $current = [];
        }

        if (str_starts_with($line, 'DTSTART')) {
            $date = substr($line, strpos($line, ':') + 1);
            $dt = DateTime::createFromFormat('Ymd\THis', $date)
                ?: DateTime::createFromFormat('Ymd', $date);
            if ($dt) {
                $current['date'] = $dt->format('d.m.Y H:i');
            }
        }

        if (str_starts_with($line, 'SUMMARY:')) {
            $current['topic'] = substr($line, 8);
        }

        if ($line === 'END:VEVENT') {
            if (!empty($current)) {
                $events[] = $current;
            }
        }
    }

    usort($events, fn($a, $b) =>
        strtotime($a['date'] ?? '') <=> strtotime($b['date'] ?? '')
    );

    return $events;
}

$page_title = 'Schedule – ' . $course['title'];

$layout_mode = 'admin';
require_once $root . '/hn_core/layout/header.php';
?>

<main class="page">

<header class="page-header">
    <h1><?= h($course['title']) ?></h1>
    <p>Last opp ny ICS-fil</p>
</header>

<section class="card" style="max-width:600px;margin:0 auto;">

<form method="post"
      enctype="multipart/form-data"
      style="display:grid;gap:10px;">

    <input type="file"
           name="ics"
           accept=".ics"
           required>

    <button class="btn-primary">
        Last opp ICS
    </button>

</form>

</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>
