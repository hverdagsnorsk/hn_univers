<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['admin'])) {
    http_response_code(403);
    exit('Ingen tilgang');
}
