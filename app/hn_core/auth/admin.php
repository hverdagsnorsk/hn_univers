<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| HN Core – Admin Guard (Authoritative)
|------------------------------------------------------------
| - Krever $_SESSION['admin'] === true
| - Håndterer session-timeout
| - Stopper alltid ved manglende tilgang
|------------------------------------------------------------
*/

if (php_sapi_name() !== 'cli') {

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header('Location: /hn_admin/login.php');
        exit;
    }

    /* ---- Session timeout (30 min) ---- */

    $timeout = 1800;

    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > $timeout)
    ) {
        session_unset();
        session_destroy();

        header('Location: /hn_admin/login.php?timeout=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}