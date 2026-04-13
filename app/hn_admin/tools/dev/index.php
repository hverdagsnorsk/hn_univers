<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../hn_core/inc/bootstrap.php';
require_once __DIR__ . '/../../../hn_core/auth/admin.php';

$page = $_GET['page'] ?? 'dashboard';

require __DIR__ . '/layout.php';