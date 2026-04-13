<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use HnAdmin\Repository\TextRepository;

$repo = new TextRepository();

echo $repo->test();
