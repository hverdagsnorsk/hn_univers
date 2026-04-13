<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| DATABASE – HVERDAGSNORSK BOOKS PLATFORM
|--------------------------------------------------------------------------
| Én PDO-instans
| - brukes av admin, tasks, scan, AI
| - lastes via engine/bootstrap.php
|--------------------------------------------------------------------------
*/

// === KONFIG ===
// Du kan senere flytte dette til .env hvis ønskelig
$dbHost = 'hverdagsnorskn03.mysql.domeneshop.no';
$dbName = 'hverdagsnorskn03';
$dbUser = 'hverdagsnorskn03';
$dbPass = 'mKUpt4Bv1!guxe672';
$dbCharset = 'utf8mb4';

// === DSN ===
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

// === PDO OPTIONS ===
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// === OPPRETT PDO ===
try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {

    // Ikke lekke DB-info i prod
    http_response_code(500);
    echo 'Databaseforbindelse feilet.';
    exit;
}
