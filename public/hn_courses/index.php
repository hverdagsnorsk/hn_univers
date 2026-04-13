<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/hn_core/inc/bootstrap.php';

use HnCourses\Controller\CourseController;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$prefix = '/hn_courses';

/* Fjern prefix */
if (str_starts_with($uri, $prefix)) {
    $uri = substr($uri, strlen($prefix));
}

/* 🔥 Normalisering */
$uri = preg_replace('#^/index\.php#', '', $uri);
$uri = rtrim($uri, '/');
$uri = $uri === '' ? '/' : $uri;

/* Routing */
switch (true) {

    /* Kursliste */
    case $uri === '/':
        (new CourseController())->index();
        break;

    /* Kursside */
    case preg_match('#^/course/([\w\-]+)$#', $uri, $m):
        (new CourseController())->show($m[1]);
        break;

    /* 🔥 NY: Klasseside */
    case preg_match('#^/class/(\d+)$#', $uri, $m):
        (new CourseController())->class((int)$m[1]);
        break;

    /* 404 */
    default:
        http_response_code(404);
        echo '404';
}