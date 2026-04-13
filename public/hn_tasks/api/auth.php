<?php
declare(strict_types=1);

session_start();

require_once dirname(__DIR__, 3) . '/app/hn_core/inc/bootstrap.php';

use HnTasks\Controller\AuthController;
use HnCore\Database\DatabaseManager;

header('Content-Type: application/json');

$pdo = DatabaseManager::get('main');
$auth = new AuthController($pdo);

$data = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'register':
        echo json_encode($auth->register(
            $data['name'],
            $data['email'],
            $data['password']
        ));
        break;

    case 'login':
        echo json_encode($auth->login(
            $data['email'],
            $data['password']
        ));
        break;

    case 'logout':
        $auth->logout();
        echo json_encode(['status' => 'ok']);
        break;

    case 'me':
        echo json_encode($auth->user());
        break;
}