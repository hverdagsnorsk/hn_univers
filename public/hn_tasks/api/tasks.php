<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnCore\Database\DatabaseManager;
use HnTasks\Controller\TaskController;

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

$level = $_GET['level'] ?? '';

if ($level === '') {
    echo json_encode(['error' => 'missing_level']);
    exit;
}

$pdo = DatabaseManager::get('main');

$controller = new TaskController($pdo);

$result = $controller->getTasks($level);

echo json_encode($result);