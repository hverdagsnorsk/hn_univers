<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/auth.php';

portal_start_session();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');

    if ($user === PORTAL_USER && password_verify($pass, PORTAL_PASS_HASH)) {
        $_SESSION['portal_logged_in'] = 1;
        session_regenerate_id(true);
        header('Location: /index.php');
        exit;
    }

    $error = 'Feil brukernavn eller passord.';
}
?><!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Portal – innlogging</title>
  <link rel="stylesheet" href="/assets/portal.css">
</head>
<body>
  <main class="wrap">
    <h1>HN-portalen</h1>

    <?php if ($error): ?>
      <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" class="card">
      <label>
        Brukernavn
        <input name="user" autocomplete="username" required>
      </label>

      <label>
        Passord
        <input type="password" name="pass" autocomplete="current-password" required>
      </label>

      <button type="submit">Logg inn</button>
    </form>
  </main>
</body>
</html>
