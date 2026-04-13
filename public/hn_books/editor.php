<?php
declare(strict_types=1);

ob_start(); // 🔴 KRITISK TEST

require_once __DIR__ . '/../../app/hn_core/inc/bootstrap.php';

use HnBooks\Controller\EditorController;

(new EditorController())->index();

ob_end_flush();