<?php
declare(strict_types=1);

/**
 * ROOT = /www
 */
$root = dirname(__DIR__, 2);

/**
 * Riktig bootstrap-path (med /app/)
 */
require_once $root . '/app/hn_core/inc/bootstrap.php';

use HnCore\Auth\Auth;

/* ALREADY LOGGED IN */
if (Auth::isAdmin()) {
    header('Location: /hn_admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $adminPass = $_ENV['ADMIN_PASSWORD'] ?? '';

    if ($adminPass && hash_equals($adminPass, $password)) {

        Auth::login();
        Auth::redirectAfterLogin();
    }

    $error = 'Feil passord';
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – Hverdagsnorsk</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>

<body class="layout-auth">

<header class="hn-topbar">
    <div class="hn-topbar__inner">
        <a href="/" class="hn-logo">
            <img src="/assets/img/logo_transparent.png" alt="Hverdagsnorsk">
        </a>
    </div>
</header>

<main class="hn-container hn-auth-container">

    <div class="hn-card hn-auth-card">

        <div class="hn-card__header">
            <h1>Administrasjon</h1>
            <p class="hn-muted">
                Tilgang til leksikon, kurs, grammatikk og AI-verktøy
            </p>
        </div>

        <?php if ($error): ?>
            <div class="hn-alert hn-alert--danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off" class="hn-form">

            <div class="hn-form-group">
                <label for="password">Administrator-passord</label>

                <div class="hn-input-with-icon">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="hn-input"
                        required
                        autofocus
                    >
                    <button type="button" class="hn-icon-button" onclick="togglePassword()">
                        👁
                    </button>
                </div>
            </div>

            <button type="submit" class="hn-button hn-button--primary hn-button--full">
                Logg inn
            </button>

        </form>

    </div>

</main>

<script>
function togglePassword() {
    const input = document.getElementById('password');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>