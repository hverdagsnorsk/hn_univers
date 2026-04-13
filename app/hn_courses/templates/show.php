<?php
declare(strict_types=1);

require HN_APP_ROOT . '/hn_courses/templates/course_header.php';

/* ==========================================================
   SORTERING + STATUS
========================================================== */

$now = date('Y-m-d H:i:s');

usort($events, fn($a, $b) => strcmp($a['start_datetime'], $b['start_datetime']));

/* ==========================================================
   RESSURS SORTERING
========================================================== */

$videos = [];
$docs   = [];
$links  = [];

foreach ($resources as $r) {

    $type = $r['type'] ?? '';

    switch ($type) {
        case 'youtube':
        case 'video':
            $videos[] = $r;
            break;

        case 'link':
            $links[] = $r;
            break;

        default:
            $docs[] = $r;
    }
}
?>

<div class="course-layout">

    <!-- ======================================================
         VENSTRE: TIMELINE
    ======================================================= -->
    <aside class="course-left">

        <h2>Kursplan</h2>

        <?php if (empty($events)): ?>
            <p class="empty">Ingen kursdatoer.</p>
        <?php else: ?>

            <?php $nextFound = false; ?>

            <div class="timeline">

                <?php foreach ($events as $e): ?>

                    <?php
                    $isPast = $e['start_datetime'] < $now;
                    $isNext = false;

                    if (!$isPast && !$nextFound) {
                        $isNext = true;
                        $nextFound = true;
                    }

                    $classes = [];
                    if ($isPast) $classes[] = 'past';
                    if ($isNext) $classes[] = 'active';
                    ?>

                    <div class="timeline-item <?= implode(' ', $classes) ?>">

                        <div class="timeline-dot"></div>

                        <div class="timeline-content">

                            <div class="event-date">
                                <?= date('d.m.Y H:i', strtotime($e['start_datetime'])) ?>
                            </div>

                            <?php if (!empty($e['location'])): ?>
                                <div class="event-location">
                                    📍 <?= htmlspecialchars($e['location']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($isNext): ?>
                                <div class="badge">Neste</div>
                            <?php elseif ($isPast): ?>
                                <div class="badge muted">Fullført</div>
                            <?php endif; ?>

                            <?php if (!empty($e['meeting_url'])): ?>
                                <a class="btn" href="<?= htmlspecialchars($e['meeting_url']) ?>" target="_blank">
                                    Nettkurs
                                </a>
                            <?php else: ?>
                                <a class="btn" href="/hn_courses/class/<?= (int)$e['id'] ?>">
                                    Se økt
                                </a>
                            <?php endif; ?>

                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </aside>

    <!-- ======================================================
         HØYRE: RESSURSER
    ======================================================= -->
    <main class="course-right">

        <h2>Ressurser</h2>

        <!-- ===================== VIDEO ===================== -->
        <?php if (!empty($videos)): ?>
            <h3>Video</h3>

            <div class="video-grid">

                <?php foreach ($videos as $v): ?>

                    <?php if (!empty($v['youtube_id'])): ?>

                        <div class="video-card">
                            <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($v['youtube_id']) ?>" target="_blank">

                                <img
                                    src="https://img.youtube.com/vi/<?= htmlspecialchars($v['youtube_id']) ?>/hqdefault.jpg"
                                    alt="Video thumbnail"
                                >

                                <div class="video-title">
                                    ▶ <?= htmlspecialchars($v['title'] ?? 'Se video') ?>
                                </div>

                            </a>
                        </div>

                    <?php elseif (!empty($v['url'])): ?>

                        <div class="video-card">
                            <a href="<?= htmlspecialchars($v['url']) ?>" target="_blank">
                                ▶ <?= htmlspecialchars($v['title'] ?? 'Se video') ?>
                            </a>
                        </div>

                    <?php endif; ?>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

        <!-- ===================== DOKUMENTER ===================== -->
        <?php if (!empty($docs)): ?>
            <h3>Dokumenter</h3>

            <ul class="resource-list">
                <?php foreach ($docs as $r): ?>
                    <li>
                        <a href="<?= htmlspecialchars($r['url'] ?? '#') ?>" target="_blank">
                            📄 <?= htmlspecialchars($r['title'] ?? 'Dokument') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- ===================== LENKER ===================== -->
        <?php if (!empty($links)): ?>
            <h3>Lenker</h3>

            <ul class="resource-list">
                <?php foreach ($links as $r): ?>
                    <li>
                        <a href="<?= htmlspecialchars($r['url'] ?? '#') ?>" target="_blank">
                            🔗 <?= htmlspecialchars($r['title'] ?? 'Lenke') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

    </main>

</div>