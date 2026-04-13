<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| system-test.php
| Testversjon av system.php med ny CSS
|------------------------------------------------------------
*/

require_once __DIR__ . '/bootstrap.php';

?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <title>System – test</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="admin-test.css">
</head>

<body>

<header class="admin-header">
  <h1>Systemoversikt</h1>
  <p>Testvisning med ny CSS (admin-test.css)</p>
</header>

<main class="admin-container">

  <section class="admin-section">
    <h2>Status</h2>

    <table class="admin-table">
      <thead>
        <tr>
          <th>Komponent</th>
          <th>Status</th>
          <th>Kommentar</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Database</td>
          <td><span class="badge badge-ok">OK</span></td>
          <td>Tilkobling aktiv</td>
        </tr>
        <tr>
          <td>JSON-mapper</td>
          <td><span class="badge badge-ok">OK</span></td>
          <td>Alle påkrevde mapper finnes</td>
        </tr>
        <tr>
          <td>E-post</td>
          <td><span class="badge badge-fail">Feil</span></td>
          <td>Ikke konfigurert</td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="admin-section">
    <h2>Testskjema</h2>

    <form method="post">
      <label>
        Kommentar
        <textarea name="comment"></textarea>
      </label>
      <br><br>
      <button type="submit">Lagre</button>
    </form>
  </section>

</main>

</body>
</html>
