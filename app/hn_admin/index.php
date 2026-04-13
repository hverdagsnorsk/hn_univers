<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$page_title  = "HN Admin";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';
?>

<main class="page admin-page">

<header class="page-header">
    <h1>HN Admin</h1>
    <p class="page-intro">
        Sentral administrasjon av hele HN-universet
    </p>
</header>

<!-- ==========================================================
     INNHOLD
========================================================== -->

<section class="admin-card-section">
    <h2>Innhold</h2>

    <div class="admin-course-grid">

        <div class="admin-course-card">
            <h3>Opprett ny tekst</h3>
            <p>Lag ny tekst direkte i valgt bok.</p>
            <a href="editor.php" class="hn-btn">
                Opprett tekst
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Administrer tekster</h3>
            <p>Rediger, flytt og organiser eksisterende tekster.</p>
            <a href="texts.php" class="hn-btn">
                Åpne tekstoversikt
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Oppgaver</h3>
            <p>Administrer oppgaver og oppgavesett.</p>
            <a href="tasks.php" class="hn-btn">
                Åpne
            </a>
        </div>

        <div class="admin-course-card">
            <h3>AI-verktøy</h3>
            <p>Generer og analyser oppgaver.</p>
            <a href="ai_generate.php" class="hn-btn">
                Åpne
            </a>
        </div>

    </div>
</section>

<!-- ==========================================================
     KURSMODUL
========================================================== -->

<section class="admin-card-section">
    <h2>Kursmodul</h2>

    <div class="admin-course-grid">

        <!-- 🔥 NY: OPPRETT KURS -->
        <div class="admin-course-card">
            <h3>Opprett kurs</h3>
            <p>Opprett nytt kurs med automatisk bok og struktur.</p>
            <a href="/hn_courses/admin/create.php" class="hn-btn hn-btn-primary">
                + Nytt kurs
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Kurssider</h3>
            <p>Åpne den offentlige oversikten over alle aktive kurs.</p>
            <a href="/hn_courses/public/index.php" class="hn-btn">
                Åpne kurssider
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Kursadmin</h3>
            <p>Administrer kurs, kursinnhold, ressurser og struktur.</p>
            <a href="/hn_courses/admin/index.php" class="hn-btn">
                Åpne kursdashboard
            </a>
        </div>

    </div>
</section>

<!-- ==========================================================
     LEKSIKON & AI
========================================================== -->

<section class="admin-card-section">
    <h2>Leksikon & AI</h2>

    <div class="admin-course-grid">

        <div class="admin-course-card">
            <h3>Lex Admin</h3>
            <p>Administrer oppslag, grammatikk og forklaringer.</p>
            <a href="lex_dashboard.php" class="hn-btn">
                Åpne Lex Admin
            </a>
        </div>

    </div>
</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>