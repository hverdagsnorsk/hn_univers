<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function portal_start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(PORTAL_SESSION_NAME);
        session_start();
    }
}

function portal_is_logged_in(): bool {
    portal_start_session();
    return !empty($_SESSION['portal_logged_in']);
}

function require_portal_auth(): void {
    if (!portal_is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}
