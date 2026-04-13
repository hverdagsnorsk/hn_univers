<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$page_title  = "Lex – Dokumentasjon";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';
?>

<main class="page admin-page">

<header class="page-header">
    <h1>Lex – Dokumentasjon</h1>
    <p class="page-intro">
        Teknisk dokumentasjon for Lex-motoren
    </p>
</header>

<section class="admin-card-section">
    <div class="admin-course-grid">

        <div class="admin-course-card">
            <h3>Arkitektur</h3>
            <a href="docs/01_architecture.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Lookup Pipeline</h3>
            <a href="docs/02_lookup_pipeline.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>AI Pipeline</h3>
            <a href="docs/03_ai_generation_pipeline.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Disambiguering</h3>
            <a href="docs/04_disambiguation.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Database</h3>
            <a href="docs/05_database_schema.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Admin System</h3>
            <a href="docs/06_admin_system.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Roadmap v2</h3>
            <a href="docs/roadmap_v2.md" class="hn-btn">Åpne</a>
        </div>

        <div class="admin-course-card">
            <h3>Changelog</h3>
            <a href="docs/CHANGELOG.md" class="hn-btn">Åpne</a>
        </div>

    </div>
</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>