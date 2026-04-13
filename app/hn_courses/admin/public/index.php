<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

use HnCourses\Admin\Controller\CourseController;
use HnCourses\Admin\Controller\ResourceController;
use HnCourses\Admin\Controller\CourseContentController;
use HnCourses\Admin\Controller\UploadController;
use HnCourses\Admin\Controller\ScheduleController;

/* =========================
   INPUT
========================= */

$action   = $_GET['action'] ?? 'courses';
$courseId = (int)($_GET['course_id'] ?? 0);
$id       = (int)($_GET['id'] ?? 0);

/* =========================
   NON-RENDER ACTIONS
   (POST / redirect)
========================= */

$noRenderActions = [
    'course_store',
    'course_update',

    'attach',
    'attach_resource',
    'upload',
    'upload_resource',
    'delete_resource_map',
    'delete_resource',
    'update_resource',
    'toggle_resource',
    'reorder_resources',

    'save_event',
    'delete_event',
    'import_ics'
];

if (in_array($action, $noRenderActions, true)) {

    switch ($action) {

        /* =========================
           COURSES
        ========================= */

        case 'course_store':
            (new CourseController())->store();
            break;

        case 'course_update':
            (new CourseController())->update($courseId);
            break;

        /* =========================
           COURSE CONTENT
        ========================= */

        case 'attach':
        case 'attach_resource':
            (new CourseContentController())->attach();
            break;

        case 'upload':
        case 'upload_resource':
            (new CourseContentController())->upload();
            break;

        case 'delete_resource_map':
        case 'delete_resource':
            (new CourseContentController())->delete_resource_map();
            break;

        case 'update_resource':
            (new CourseContentController())->update();
            break;

        case 'toggle_resource':
            (new CourseContentController())->toggle();
            break;

        case 'reorder_resources':
            (new CourseContentController())->reorder();
            break;

        /* =========================
           SCHEDULE
        ========================= */

        case 'save_event':
            (new ScheduleController())->save();
            break;

        case 'delete_event':
            (new ScheduleController())->delete($courseId, $id);
            break;

        case 'import_ics':
            (new ScheduleController())->importIcs($courseId);
            break;
    }

    exit; // 🔥 KRITISK: stopper før header render
}

/* =========================
   LAYOUT START
========================= */

$page_title   = "HN Courses – Admin";
$layout_mode  = 'admin';
$page_context = 'course_admin';

require_once $root . '/hn_core/layout/header.php';

echo '<main class="page admin-page">';

/* =========================
   RENDER ROUTING
========================= */

switch ($action) {

    /* =========================
       COURSES
    ========================= */

    case 'courses':
        (new CourseController())->index();
        break;

    case 'course_create':
        (new CourseController())->create();
        break;

    case 'course_edit':
        (new CourseController())->edit($courseId);
        break;

    /* =========================
       RESOURCES (GLOBAL)
    ========================= */

    case 'resources':
        (new ResourceController())->index();
        break;

    /* =========================
       COURSE CONTENT
    ========================= */

    case 'course_content':
        (new CourseContentController())->index();
        break;

    /* =========================
       FALLBACK
    ========================= */

    default:
        echo "<div class='admin-error'>";
        echo "<h2>Ukjent action</h2>";
        echo "<p>" . htmlspecialchars((string)$action) . "</p>";
        echo "</div>";
        break;
}

/* =========================
   LAYOUT END
========================= */

echo '</main>';

require_once $root . '/hn_core/layout/footer.php';