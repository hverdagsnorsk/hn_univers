<?php
echo "<div class='card'>";
echo "<h3>Database Tables</h3>";

$stmt = db()->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "<strong>{$table}</strong><br>";

    $cols = db()->query("SHOW COLUMNS FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";

    foreach ($cols as $col) {
        echo "<tr>
                <td>{$col['Field']}</td>
                <td>{$col['Type']}</td>
                <td>{$col['Null']}</td>
                <td>{$col['Key']}</td>
              </tr>";
    }

    echo "</table><br>";
}

echo "</div>";