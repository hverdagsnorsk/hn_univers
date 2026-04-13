<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);

require_once $root . '/app/hn_core/inc/bootstrap.php';

use HnLex\Action\RejectEntryAction;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Method not allowed";
    exit;
}

try {

    $action = new RejectEntryAction();
    $action->handle($_POST);

    header('Location: /hn_lex/admin/index.php?status=rejected');
    exit;

} catch (Throwable $e) {

    http_response_code(500);
    echo "FAILED\n";
    echo $e->getMessage();
}