<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------
 | engine/bootstrap.php
 |------------------------------------------------------------
 | Felles oppstart for admin, tasks, scan, osv.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/* Plattform-root */
define('HN_ROOT', realpath(__DIR__ . '/..'));

/* Books-root */
define('BOOKS_DIR', HN_ROOT . '/books');

define('TEACHER_EMAIL', 'svar@hverdagsnorsk.no');
define('SITE_NAME', 'Hverdagsnorsk');
define('SITE_URL', 'https://hverdagsnorsk.no');

