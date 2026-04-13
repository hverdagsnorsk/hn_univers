<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$page_title = 'AI Queue Monitor';
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';

/* ==========================================================
   TIME WINDOW
========================================================== */

$cutoff = (new DateTimeImmutable('-24 hours'))
    ->format('Y-m-d H:i:s');

/* ==========================================================
   CORE COUNTS
========================================================== */

$stats = [];

$stats['total_jobs'] = (int)$pdo_lex->query("
    SELECT COUNT(*) FROM lex_ai_jobs
")->fetchColumn();

$stats['total_done'] = (int)$pdo_lex->query("
    SELECT COUNT(*) FROM lex_ai_jobs WHERE status='done'
")->fetchColumn();

$stats['pending'] = (int)$pdo_lex->query("
    SELECT COUNT(*) FROM lex_ai_jobs WHERE status='pending'
")->fetchColumn();

$stats['processing'] = (int)$pdo_lex->query("
    SELECT COUNT(*) FROM lex_ai_jobs WHERE status='processing'
")->fetchColumn();

$stats['failed_total'] = (int)$pdo_lex->query("
    SELECT COUNT(*) FROM lex_ai_jobs WHERE status='failed'
")->fetchColumn();

/* ==========================================================
   24H METRICS
========================================================== */

$stmt = $pdo_lex->prepare("
    SELECT COUNT(*)
    FROM lex_ai_jobs
    WHERE status='done'
      AND finished_at >= ?
");
$stmt->execute([$cutoff]);
$stats['done_24h'] = (int)$stmt->fetchColumn();

$stmt = $pdo_lex->prepare("
    SELECT COUNT(*)
    FROM lex_ai_jobs
    WHERE status='failed'
      AND finished_at >= ?
");
$stmt->execute([$cutoff]);
$stats['failed_24h'] = (int)$stmt->fetchColumn();

/* ==========================================================
   PERFORMANCE METRICS
========================================================== */

$avgAttempts = $pdo_lex->query("
    SELECT ROUND(AVG(attempts),2)
    FROM lex_ai_jobs
")->fetchColumn();

$avgRuntime = $pdo_lex->query("
    SELECT ROUND(AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)),2)
    FROM lex_ai_jobs
    WHERE started_at IS NOT NULL
      AND finished_at IS NOT NULL
")->fetchColumn();

/* ==========================================================
   THROUGHPUT (jobs per hour last 24h)
========================================================== */

$jobsPerHour = $stats['done_24h'] > 0
    ? round($stats['done_24h'] / 24, 2)
    : 0;

/* ==========================================================
   LAST ACTIVITY
========================================================== */

$lastActivity = $pdo_lex->query("
    SELECT MAX(finished_at)
    FROM lex_ai_jobs
")->fetchColumn();

/* ==========================================================
   HEALTH STATUS
========================================================== */

$failureRate = ($stats['done_24h'] + $stats['failed_24h']) > 0
    ? round(
        ($stats['failed_24h'] /
        ($stats['done_24h'] + $stats['failed_24h'])) * 100
      )
    : 0;

if ($stats['processing'] > 0) {
    $health = "Processing";
} elseif ($failureRate > 20) {
    $health = "High Failure Rate";
} elseif ($stats['pending'] > 50) {
    $health = "Backlog Pressure";
} else {
    $health = "Healthy";
}
?>

<section class="hn-section">
<div class="hn-container">

<h1 class="hn-title"><?= h($page_title) ?></h1>

<div class="hn-grid hn-mb-4">

<div>Health: <strong><?= h($health) ?></strong></div>
<div>Total Jobs: <strong><?= $stats['total_jobs'] ?></strong></div>
<div>Total Done: <strong><?= $stats['total_done'] ?></strong></div>
<div>Pending: <strong><?= $stats['pending'] ?></strong></div>
<div>Processing: <strong><?= $stats['processing'] ?></strong></div>
<div>Failed Total: <strong><?= $stats['failed_total'] ?></strong></div>

<div>Done (24h): <strong><?= $stats['done_24h'] ?></strong></div>
<div>Failed (24h): <strong><?= $stats['failed_24h'] ?></strong></div>
<div>Failure Rate (24h): <strong><?= $failureRate ?>%</strong></div>

<div>Avg Attempts: <strong><?= $avgAttempts ?></strong></div>
<div>Avg Runtime (s): <strong><?= $avgRuntime ?></strong></div>
<div>Jobs/hour (24h): <strong><?= $jobsPerHour ?></strong></div>

<div>Last Activity: <strong><?= h($lastActivity ?? '-') ?></strong></div>

</div>

</div>
</section>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>