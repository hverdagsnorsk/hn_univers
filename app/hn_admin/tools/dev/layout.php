<?php
declare(strict_types=1);

$allowed = ['dashboard', 'files', 'db', 'system'];

if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>HN Dev Dashboard</title>

<style>
:root {
    --primary: #1f6f78;
    --primary-light: #2e8c96;
    --bg: #f5f7f9;
    --card: #ffffff;
    --border: #e3e8ee;
    --text: #1e293b;
    --muted: #64748b;
}

body {
    font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
}

.sidebar {
    width: 230px;
    background: white;
    border-right: 1px solid var(--border);
    position: fixed;
    top: 0;
    bottom: 0;
    padding: 25px 20px;
}

.sidebar h2 {
    margin-top: 0;
    font-size: 20px;
    color: var(--primary);
}

.sidebar a {
    display: block;
    padding: 8px 0;
    text-decoration: none;
    color: var(--muted);
    font-size: 14px;
}

.sidebar a.active {
    color: var(--primary);
    font-weight: 600;
}

.sidebar a:hover {
    color: var(--primary-light);
}

.main {
    margin-left: 250px;
    padding: 40px;
}

.card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

h3 {
    margin-top: 0;
    color: var(--primary);
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

td, th {
    padding: 10px;
    border-bottom: 1px solid var(--border);
}

th {
    text-align: left;
    color: var(--muted);
    font-weight: 600;
}

.folder {
    color: var(--primary);
    font-weight: 500;
}

.file {
    color: var(--text);
}

.small {
    color: var(--muted);
    font-size: 12px;
}

a {
    color: var(--primary);
}

a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>HN Dev</h2>
    <?php require __DIR__ . '/_nav.php'; ?>
</div>

<div class="main">

<?php
switch ($page) {
    case 'files':
        require __DIR__ . '/file-explorer.php';
        break;

    case 'db':
        require __DIR__ . '/db-viewer.php';
        break;

    case 'system':
        require __DIR__ . '/system-info.php';
        break;

    default:
        echo "<div class='card'>";
        echo "<h3>Dashboard</h3>";
        echo "<p>Utviklerverktøy for Hverdagsnorsk.</p>";
        echo "<ul>
                <li>📁 Filutforsker</li>
                <li>🗄 Databaseoversikt</li>
                <li>⚙ Systeminformasjon</li>
              </ul>";
        echo "</div>";
}
?>

</div>
</body>
</html>