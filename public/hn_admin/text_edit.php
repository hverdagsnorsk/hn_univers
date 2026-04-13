<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

use HnAdmin\Controller\TextEditController;
use HnAdmin\Service\TextService;
use HnAdmin\Repository\TextRepository;

$pdo = db('main');

$repo = new TextRepository($pdo);
$service = new TextService($repo);
$controller = new TextEditController($service);

$controller->handle();
