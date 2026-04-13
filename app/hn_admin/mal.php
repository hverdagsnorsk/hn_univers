<?php
include('config.php');
session_start();

// --- Innlogging ---
if (isset($_POST['user'], $_POST['pass'])) {
    if ($_POST['user'] === ADMIN_USER && $_POST['pass'] === ADMIN_PASS) {
        $_SESSION['admin'] = true;
    } else {
        $error = "Feil brukernavn eller passord.";
    }
}

// --- Logg ut ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// --- Krev innlogging ---
if (empty($_SESSION['admin'])):
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Admin – Norsk for renholdere</title>
<style>
body { font-family: 'Segoe UI', sans-serif; background: #f5f7f7; text-align: center; margin-top: 100px; }
form { display: inline-block; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
input { margin: 5px 0; padding: 8px; width: 200px; border-radius: 6px; border: 1px solid #ccc; }
button { background: #2b7a78; color: white; padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; }
button:hover { background: #205f5d; }
.error { color: red; }
</style>
</head>
<body>
<h2>Admininnlogging</h2>
<form method="post">
  <input type="text" name="user" placeholder="Brukernavn" required><br>
  <input type="password" name="pass" placeholder="Passord" required><br>
  <button type="submit">Logg inn</button>
</form>
<p class="error"><?= $error ?? '' ?></p>
</body>
</html>
<?php
exit;
endif;

// --- Lagre endringer ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
    $type = $_POST['type'];
    $data = json_decode($_POST['jsondata'], true);
    if ($data === null) {
        $msg = "Feil: ugyldig JSON-format.";
    } else {
        switch ($type) {
            case 'docs': save_json(DOC_FILE, $data); break;
            case 'vids': save_json(VID_FILE, $data); break;
            case 'schedule': save_json(SCH_FILE, $data); break;
        }
        $msg = "✅ Endringer lagret!";
    }
}

$docs = read_json(DOC_FILE);
$vids = read_json(VID_FILE);
$schedule = read_json(SCH_FILE);
?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Adminpanel – Norsk for renholdere</title>
<style>
body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f5f7f7; color: #333; }
header { background: #2b7a78; color: white; padding: 20px; text-align: center; }
main { max-width: 900px; margin: 30px auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
section { margin-bottom: 30px; }
textarea { width: 100%; height: 200px; font-family: monospace; }
button { background: #2b7a78; color: white; border: none; border-radius: 6px; padding: 10px 16px; cursor: pointer; }
button:hover { background: #205f5d; }
.msg { color: green; margin-bottom: 15px; }
.logout { position: absolute; top: 20px; right: 20px; background: #f2f2f2; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #333; }
.logout:hover { background: #ddd; }
</style>
</head>
<body>
<header>
  <h1>Adminpanel – Norsk for renholdere</h1>
  <a href="?logout=1" class="logout">Logg ut</a>
</header>
<main>
<?php if (!empty($msg)) echo "<p class='msg'>$msg</p>"; ?>

<section>
  <h2>📄 Dokumenter</h2>
  <form method="post">
    <input type="hidden" name="type" value="docs">
    <textarea name="jsondata"><?= htmlspecialchars(json_encode($docs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea><br>
    <button type="submit">Lagre dokumenter</button>
  </form>
</section>

<section>
  <h2>🎥 Videoer</h2>
  <form method="post">
    <input type="hidden" name="type" value="vids">
    <textarea name="jsondata"><?= htmlspecialchars(json_encode($vids, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea><br>
    <button type="submit">Lagre videoer</button>
  </form>
</section>

<section>
  <h2>🗓️ Kursplan</h2>
  <form method="post">
    <input type="hidden" name="type" value="schedule">
    <textarea name="jsondata"><?= htmlspecialchars(json_encode($schedule, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></textarea><br>
    <button type="submit">Lagre kursplan</button>
  </form>
</section>
</main>
</body>
</html>
