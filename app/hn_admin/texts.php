<?php

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use HnAdmin\Controller\TextController;

(new TextController())->index();