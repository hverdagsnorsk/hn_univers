<?php
declare(strict_types=1);
?>

<link rel="stylesheet" href="/assets/css/course/course.css">

<div class="course-header">

    <h1><?= htmlspecialchars($course['title']) ?></h1>

    <?php if (!empty($course['description'])): ?>
        <p class="course-description">
            <?= nl2br(htmlspecialchars($course['description'])) ?>
        </p>
    <?php endif; ?>

    <?php if (!empty($next_event)): ?>
        <div class="next-course">
            <strong>Neste kurs:</strong>
            <?= date('d.m.Y H:i', strtotime($next_event['start_datetime'])) ?>

            <?php if (!empty($next_event['meeting_url'])): ?>
                – <a href="<?= htmlspecialchars($next_event['meeting_url']) ?>" target="_blank">
                    Gå til nettkurs
                </a>
            <?php elseif (!empty($next_event['location'])): ?>
                – <?= htmlspecialchars($next_event['location']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- CTA: BOK -->
    <?php if (!empty($course['book_slug'])): ?>
        <div class="course-cta">
            <a href="/hn_books/books/<?= htmlspecialchars($course['book_slug']) ?>/index.php" class="btn-primary">
                📘 Gå til kursbok
            </a>
        </div>
    <?php endif; ?>

</div>