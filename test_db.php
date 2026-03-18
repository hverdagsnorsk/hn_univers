<?php

require_once __DIR__.'/hn_core/inc/bootstrap.php';

$pdo = db('main');

echo $pdo->query("SELECT DATABASE()")->fetchColumn() . PHP_EOL;
