<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once __DIR__ . '/../../app/hn_core/inc/bootstrap.php';

use HnCore\Auth\Auth;

Auth::requireAdmin();
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin</title>
</head>
<body>

<h1>Adminpanel</h1>

<p>Du er innlogget.</p>

<p><a href="/hn_admin/logout.php">Logg ut</a></p>

</body>
</html>