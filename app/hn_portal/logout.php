<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/auth.php';

portal_start_session();
$_SESSION = [];
session_destroy();

header('Location: /login.php');
exit;
