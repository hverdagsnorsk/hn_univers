<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN LEX – Module Bootstrap
|--------------------------------------------------------------------------
| Laster core og setter riktig DB-connection for LEX-modulen
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../hn_core/inc/bootstrap.php';

if (!isset($pdo_lex) || !$pdo_lex instanceof PDO) {
    throw new RuntimeException('Lex database connection missing');
}

/*
|--------------------------------------------------------------------------
| Adapter: gjør $pdo til lex-databasen lokalt i LEX-modulen
|--------------------------------------------------------------------------
*/

$pdo = $pdo_lex;
