<?php
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/hn_core/inc/bootstrap.php';

/* ALREADY LOGGED IN */
if (!empty($_SESSION['admin'])) {
    header('Location: /hn_admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $adminPass = $_ENV['ADMIN_PASSWORD'] ?? '';

    if ($adminPass && hash_equals($adminPass, $password)) {

        $_SESSION['admin'] = true;
        $_SESSION['last_activity'] = time();

        header('Location: /hn_admin/index.php');
        exit;
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

<style>
/* === INTEGRERT DESIGN === */

body.layout-auth {
    font-family: system-ui, -apple-system, BlinkMacSystemFont,
                 "Segoe UI", Roboto, "Noto Sans", Arial, sans-serif;
    background: #f5f7f7;
    padding: 30px;
    max-width: 1100px;
    margin: auto;
    color: #222;
}

/* TYPO */
.hn-card__header h1 {
    color: #2f8485;
    margin-bottom: 10px;
}

.hn-card__header p {
    color: #444;
}

/* FORM */
.hn-form-group label {
    font-weight: 600;
    display: block;
    margin-top: 20px;
    margin-bottom: 6px;
}

/* INPUT */
.hn-input {
    width: 100%;
    padding: 10px;
    font-size: 16px;
}

/* BUTTON */
.hn-button--primary {
    margin-top: 20px;
    padding: 12px 18px;
    font-size: 16px;
    cursor: pointer;
    background-color: #2f8485;
    color: #fff;
    border: none;
    border-radius: 6px;
}

.hn-button--primary:hover {
    background-color: #226c6d;
}

/* CARD */
.hn-auth-card {
    background: #fff;
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #ccc;
    max-width: 420px;
    margin: 40px auto;
}

/* ALERT */
.hn-alert--danger {
    background: #ffe5e5;
    border: 1px solid #ffb3b3;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
}

/* INPUT ICON FIX */
.hn-input-with-icon {
    display: flex;
    align-items: center;
}

.hn-input-with-icon input {
    flex: 1;
}

.hn-icon-button {
    margin-left: 8px;
    padding: 10px;
    background: #eee;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.hn-icon-button:hover {
    background: #ddd;
}
</style>

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