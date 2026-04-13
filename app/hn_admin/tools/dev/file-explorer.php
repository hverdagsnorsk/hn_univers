<?php
$basePath = realpath(__DIR__ . '/../../../');
$current = $_GET['path'] ?? '';
$current = trim($current, '/');
$full = realpath($basePath . '/' . $current);

if ($full === false || !str_starts_with($full, $basePath)) {
    echo "<div class='card'>Invalid path</div>";
    return;
}

echo "<div class='card'>";
echo "<h3>File Explorer</h3>";
echo "<div>Path: {$full}</div><br>";

if ($current !== '') {
    $parent = dirname($current);
    echo "<a href='?page=files&path={$parent}'>⬅ Back</a><br><br>";
}

$items = scandir($full);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    if ($item === '.env') continue;

    $itemPath = $full . '/' . $item;

    if (is_dir($itemPath)) {
        $link = trim($current . '/' . $item, '/');
        echo "📁 <a href='?page=files&path={$link}'>{$item}</a><br>";
    } else {
        $size = round(filesize($itemPath) / 1024, 2);
        echo "📄 {$item} ({$size} KB)<br>";
    }
}

echo "</div>";