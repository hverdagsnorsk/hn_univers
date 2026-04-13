<?php
declare(strict_types=1);

/* ==========================================================
   HN CONTROL CENTER
   Scanner alle hn_* moduler i /www/
========================================================== */

$root = dirname(__DIR__, 2); // www/
$modules = [];

foreach (scandir($root) as $dir) {

    if ($dir === '.' || $dir === '..') {
        continue;
    }

    $path = $root . '/' . $dir;

    if (!is_dir($path)) {
        continue;
    }

    // Tillat både hn_ og HN_
    if (!preg_match('/^hn_/i', $dir)) {
        continue;
    }

    $module = [
        'name'  => $dir,
        'admin' => null,
        'index' => null,
        'cli'   => false,
    ];

    if (file_exists($path . '/admin/index.php')) {
        $module['admin'] = '/' . $dir . '/admin/';
    }

    if (file_exists($path . '/index.php')) {
        $module['index'] = '/' . $dir . '/';
    }

    if (is_dir($path . '/cli')) {
        $module['cli'] = true;
    }

    $modules[] = $module;
}

/* Sorter alfabetisk */
usort($modules, fn($a, $b) => strcasecmp($a['name'], $b['name']));
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>HN Portal – Control Center</title>
<style>
body {
    font-family: system-ui;
    padding:2rem;
    background:#f5f7fa;
}

.card {
    background:white;
    padding:1.2rem;
    margin-bottom:1rem;
    border-radius:8px;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

h2 {
    margin:0 0 .6rem 0;
}

a {
    display:inline-block;
    margin-right:1rem;
    text-decoration:none;
    color:#2563eb;
}

.badge {
    font-size:0.8rem;
    background:#eee;
    padding:.25rem .5rem;
    border-radius:4px;
}
</style>
</head>
<body>

<h1>HN Control Center</h1>

<?php foreach ($modules as $m): ?>
<div class="card">

    <h2><?= htmlspecialchars($m['name']) ?></h2>

    <?php if ($m['admin']): ?>
        <a href="<?= $m['admin'] ?>">Admin</a>
    <?php endif; ?>

    <?php if ($m['index']): ?>
        <a href="<?= $m['index'] ?>">Frontend</a>
    <?php endif; ?>

    <?php if ($m['cli']): ?>
        <span class="badge">CLI tools</span>
    <?php endif; ?>

</div>
<?php endforeach; ?>

</body>
</html>
