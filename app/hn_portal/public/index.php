<?php
declare(strict_types=1);

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

$page = $_GET['page'] ?? 'hjem';

/**
 * Sikkerhet: kun lov med enkle navn
 */
$page = preg_replace('/[^a-z0-9\-]/', '', strtolower($page));

$file = __DIR__ . '/../pages/' . $page . '.md';

if (!is_file($file)) {
    http_response_code(404);
    echo "<h1>Side ikke funnet</h1>";
    exit;
}

$md = file_get_contents($file);

$parser = new Parsedown();
$html = $parser->text($md);

/**
 * Render template
 */
require __DIR__ . '/../templates/layout.php';