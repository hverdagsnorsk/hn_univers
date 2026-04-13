<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="no">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= htmlspecialchars($pageTitle ?? 'Hverdagsnorsk', ENT_QUOTES, 'UTF-8') ?></title>

  <?php
    $v = isset($assetVersion) ? urlencode((string)$assetVersion) : '1';
  ?>

  <!-- Leser 2026 – ENESTE STYLING -->
  <link rel="stylesheet" href="/hn_books/engine/reader_2026.css?v=<?= $v ?>">
  <script type="module" src="/hn_books/engine/reader/index.js"></script>
</head>

<body>

<div class="reader">

  <header class="header">
    <img
      src="/hn_admin/assets/fulllogo_transparent_nobuffer.png"
      class="logo"
      alt="Hverdagsnorsk logo"
    >
  </header>

  <nav class="reader-nav" data-back="../index.php"></nav>

  <main class="book-container">
    <section>

      <div class="reader-tools">
        <button data-action="decrease">A−</button>
        <button data-action="reset">A</button>
        <button data-action="increase">A+</button>
      </div>

      <h2><?= htmlspecialchars($displayTitle ?? '', ENT_QUOTES, 'UTF-8') ?></h2>

      <?= $contentHtml ?? '' ?>

    </section>
  </main>

  <footer>
    <p>© <?= date('Y') ?> Hverdagsnorsk</p>
  </footer>

</div>

</body>
</html>