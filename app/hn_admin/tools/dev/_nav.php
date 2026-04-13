<?php
function nav($label, $key, $page) {
    $active = $page === $key ? 'active' : '';
    echo "<a class='{$active}' href='?page={$key}'>{$label}</a>";
}
nav('Dashboard', 'dashboard', $page);
nav('File Explorer', 'files', $page);
nav('Database Viewer', 'db', $page);
nav('System Info', 'system', $page);