<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/hn_core/inc/bootstrap.php';

use HnAdmin\Controller\EditorController;

$controller = new EditorController();
$controller->index();