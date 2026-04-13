<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_POST['user'], $_POST['pass'])) {
    if ($_POST['user'] === ADMIN_USER && $_POST['pass'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Feil brukernavn eller passord.';
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<meta charset="UTF-8">
<title>Admin – Norsk for renholdere</title>
<style>
body{font-family:Segoe UI;background:#f5f7f7;text-align:center;margin-top:100px}
form{background:#fff;padding:30px;display:inline-block;border-radius:12px}
input,button{padding:8px;margin:6px}
.error{color:red}
</style>

<form method="post">
<h2>Admininnlogging</h2>
<input name="user" placeholder="Brukernavn" required><br>
<input name="pass" type="password" placeholder="Passord" required><br>
<button>Logg inn</button>
<p class="error"><?= $error ?? '' ?></p>
</form>
</html>
