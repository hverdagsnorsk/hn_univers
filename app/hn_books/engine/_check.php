<?php
require_once __DIR__ . '/bootstrap.php';

echo '<pre>';
echo "BOOTSTRAP OK\n";
echo "HN_ROOT: " . HN_ROOT . "\n";
echo "BOOKS_DIR: " . BOOKS_DIR . "\n";
echo "PDO class: " . get_class($pdo) . "\n";
echo '</pre>';
