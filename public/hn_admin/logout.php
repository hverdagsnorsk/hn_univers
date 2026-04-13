<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/hn_core/inc/bootstrap.php';

use HnCore\Auth\Auth;

Auth::logout();

header('Location: /hn_admin/login.php');
exit;