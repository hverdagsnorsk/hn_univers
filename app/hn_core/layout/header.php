<?php
declare(strict_types=1);

use HnCore\Assets\AssetLoader;

$layout_mode = $layout_mode ?? 'public';
$page_title  = $page_title ?? 'Hverdagsnorsk';

$currentUri  = $_SERVER['REQUEST_URI'] ?? '';
$isAdmin     = !empty($_SESSION['admin']);

/* ---------------------------------------------------
SAFE BASE CONSTANTS
--------------------------------------------------- */

$BASE = [
    'courses' => defined('HN_COURSE_BASE') ? HN_COURSE_BASE : '/hn_courses',
    'books'   => defined('HN_BOOKS_BASE')  ? HN_BOOKS_BASE  : '/hn_books',
    'lex'     => defined('HN_LEX_BASE')    ? HN_LEX_BASE    : '/hn_lex',
    'flash'   => defined('HN_FLASH_BASE')  ? HN_FLASH_BASE  : '/hn_flash',
    'admin'   => defined('HN_ADMIN_BASE')  ? HN_ADMIN_BASE  : '/hn_admin'
];

/* ---------------------------------------------------
ACTIVE LINK HELPER
--------------------------------------------------- */

function hn_active(string $uri, string $base): string
{
    return str_contains($uri, trim($base, '/')) ? 'active' : '';
}

/* ---------------------------------------------------
ASSETS
--------------------------------------------------- */

$assets = new AssetLoader();

if ($isAdmin || defined('HN_ADMIN_PAGE')) {
    $assets->enableAdmin();
}

if (defined('HN_LEX_PAGE')) {
    $assets->enableLex();
}

if (defined('HN_COURSE_PAGE')) {
    $assets->enableCourse();
}

if (defined('HN_READER_PAGE')) {
    $assets->enableReader();
}

?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>

<?= $assets->renderCss() ?>

</head>

<body class="layout-<?= htmlspecialchars($layout_mode, ENT_QUOTES, 'UTF-8') ?>">

<header class="hn-topbar">

<div class="hn-topbar__inner">

<a href="/" class="hn-logo">
<img src="/assets/img/logo_transparent.png" alt="Hverdagsnorsk">
</a>

<nav class="hn-nav">

<a href="<?= $BASE['courses'] ?>/"
class="<?= hn_active($currentUri, $BASE['courses']) ?>">
Kurs</a>

<a href="<?= $BASE['books'] ?>/"
class="<?= hn_active($currentUri, $BASE['books']) ?>">
Bøker</a>

<a href="<?= $BASE['lex'] ?>/"
class="<?= hn_active($currentUri, $BASE['lex']) ?>">
Ordbok</a>

<a href="<?= $BASE['flash'] ?>/"
class="<?= hn_active($currentUri, $BASE['flash']) ?>">
Flash</a>

<?php if ($isAdmin): ?>
<a href="<?= $BASE['admin'] ?>/"
class="<?= hn_active($currentUri, $BASE['admin']) ?>">
Admin</a>
<?php endif; ?>

</nav>

</div>

</header>

<main class="hn-container">