<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$pdo = DatabaseManager::get('main');

$stmt = $pdo->prepare("
    SELECT level, completed, last_score
    FROM hn_task_progress
    WHERE email = :email
");

$stmt->execute([
    'email' => $_SESSION['user']['email']
]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));