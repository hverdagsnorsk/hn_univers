<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/auth.php';
require_portal_auth();
?><!doctype html>
<html lang="no">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>HN-portalen</title>
  <link rel="stylesheet" href="/assets/portal.css">
</head>
<body>
  <header class="top">
    <div class="wrap top-inner">
      <strong>HN-portalen</strong>
      <nav class="top-nav">
        <a href="/logout.php">Logg ut</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    <section class="grid">
      <a class="tile" href="https://flash.hverdagsnorsk.no" target="_blank" rel="noopener">Flash</a>
      <a class="tile" href="https://hverdagsnorsk.no/hn_admin/index.php" target="_blank" rel="noopener">HN Admin</a>
      <a class="tile" href="https://hverdagsnorsk.no/hn_books/index.php" target="_blank" rel="noopener">HN Books</a>
      <a class="tile" href="https://hverdagsnorsk.no/hn_courses/index.php" target="_blank" rel="noopener">HN Courses</a>
    </section>
  </main>
</body>
</html>
