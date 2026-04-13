<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hverdagsnorsk</title>

<link rel="stylesheet" href="/assets/portal.css">

</head>
<body>

<header>
    <div class="logo">
        <img src="/assets/logo.png" alt="Hverdagsnorsk">
        <span>Hverdagsnorsk</span>
    </div>
</header>

<nav class="top-nav wrap">
    <a href="/">Hjem</a>
    <a href="/?page=kurs">Kurs</a>
    <a href="/?page=hvordan">Hvordan</a>
    <a href="/?page=laerlingstotte">Lærlingstøtte</a>
    <a href="/?page=videoer">Videoer</a>
    <a href="/?page=medlem">Medlem</a>
    <a href="/?page=ommeg">Om meg</a>
    <a href="/?page=kontakt">Kontakt</a>
</nav>

<main class="wrap">
    <?= $html ?>
</main>

<footer class="wrap">
    © <?= date('Y') ?> Hverdagsnorsk
</footer>

</body>
</html>