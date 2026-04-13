<?php 
use HnCourses\View\Helper;

$tab = $_GET['tab'] ?? 'lesson';

/* =========================
   🔑 TAB → CATEGORY
========================= */
$tabToCategory = [
    'lesson'        => 'lesson',
    'lesson_video'  => 'lesson_video',
    'documents'     => 'document',
    'video'         => 'video',
    'links'         => 'link'
];

$currentCategory = $tabToCategory[$tab] ?? 'document';

/* =========================
   🔗 URL BUILDER
========================= */
function resourceUrl($r) {

    if (!empty($r['youtube_id'])) {
        return "https://www.youtube.com/watch?v=" . $r['youtube_id'];
    }

    if (!empty($r['external_url'])) {
        return $r['external_url'];
    }

    if (!empty($r['stored_filename'])) {
        $folder = $r['is_shared'] ? "shared" : "course_" . $r['course_id'];
        return "/hn_courses/uploads/resources/{$folder}/" . $r['stored_filename'];
    }

    return "#";
}
?>

<h2><?= htmlspecialchars($course['title'] ?? '') ?></h2>

<div class="tabs">
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=lesson">Leksjon</a>
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=documents">Dokumenter</a>
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=lesson_video">Leksjonsvideo</a>
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=video">Video</a>
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=links">Lenker</a>
    <a href="?action=course_content&course_id=<?= $courseId ?>&tab=schedule">Tidsplan</a>
</div>

<hr>

<?php if ($tab === 'schedule'): ?>

<h3>Tidsplan</h3>

<!-- =========================
   LEGG TIL EVENT
========================= -->
<form method="post" action="?action=save_event">
    <input type="hidden" name="course_id" value="<?= $courseId ?>">

    <div style="display:flex; flex-wrap:wrap; gap:10px;">
        <input type="datetime-local" name="start" required>
        <input type="datetime-local" name="end">

        <input type="text" name="location" placeholder="Sted">
        <input type="text" name="meeting_url" placeholder="Møte-link">
        <input type="text" name="summary" placeholder="Tittel">

        <button type="submit">Legg til</button>
    </div>
</form>

<hr>

<!-- =========================
   LISTE
========================= -->

<?php if (empty($events)): ?>
    <p>Ingen hendelser registrert.</p>
<?php endif; ?>

<?php foreach ($events as $e): ?>
    <div class="card">

        <strong><?= htmlspecialchars($e['summary'] ?? 'Uten tittel') ?></strong><br>

        <!-- 📅 Norsk dato -->
        <?= date('d.m.Y H:i', strtotime($e['start_datetime'])) ?><br>

        <!-- 📍 Sted -->
        <?php if (!empty($e['location'])): ?>
            📍 <?= htmlspecialchars($e['location']) ?><br>
        <?php endif; ?>

        <!-- 🔗 Møtelink -->
        <?php if (!empty($e['meeting_url'])): ?>
            <a href="<?= htmlspecialchars($e['meeting_url']) ?>" target="_blank">
                Start kurs
            </a><br>
        <?php endif; ?>

        <!-- ❌ Slett -->
        <a href="?action=delete_event&id=<?= $e['id'] ?>&course_id=<?= $courseId ?>">
            Slett
        </a>

    </div>
<?php endforeach; ?>

<?php else: ?>
<!-- =========================
     UPLOAD / INPUT
========================= -->

<h3>Legg til ressurs</h3>

<form method="post" enctype="multipart/form-data"
      action="?action=upload&course_id=<?= $courseId ?>&tab=<?= $tab ?>">

    <?php if ($tab === 'links'): ?>
        <input type="text" name="title" placeholder="Tittel"><br>
        <input type="text" name="external_url" placeholder="https://..."><br>

    <?php elseif ($tab === 'video' || $tab === 'lesson_video'): ?>
        <input type="text" name="youtube_url" placeholder="YouTube URL"><br>

    <?php else: ?>
        <input type="file" name="file"><br>
    <?php endif; ?>

    <button type="submit">Last opp</button>
</form>

<hr>

<!-- =========================
     📎 ATTACHED
========================= -->

<h3>Tilknyttet til kurs</h3>

<?php if (empty($attached)): ?>
    <p>Ingen ressurser lagt til.</p>
<?php endif; ?>

<?php foreach ($attached as $r): ?>
    <div class="card">
        <a href="<?= resourceUrl($r) ?>" target="_blank">
            <?= htmlspecialchars($r['title'] ?? $r['original_filename'] ?? '') ?>
        </a>

        <form method="post" action="?action=delete_resource_map">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <button>Slett</button>
        </form>
    </div>
<?php endforeach; ?>

<hr>

<!-- =========================
     📚 LIBRARY
========================= -->

<h3>Bibliotek</h3>

<?php if (empty($resources)): ?>
    <p>Ingen ressurser i bibliotek.</p>
<?php endif; ?>

<?php foreach ($resources as $r): ?>
    <div class="card">

        <a href="<?= resourceUrl($r) ?>" target="_blank">
            <?= htmlspecialchars($r['title'] ?? $r['original_filename'] ?? '') ?>
        </a>

        <form method="post" action="?action=attach">
            <input type="hidden" name="course_id" value="<?= $courseId ?>">
            <input type="hidden" name="resource_id" value="<?= $r['id'] ?>">
            <input type="hidden" name="category" value="<?= $currentCategory ?>">
            <input type="hidden" name="tab" value="<?= $tab ?>">
            <button>Legg til</button>
        </form>

    </div>
<?php endforeach; ?>

<?php endif; ?>