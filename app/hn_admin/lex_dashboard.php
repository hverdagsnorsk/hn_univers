<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$page_title  = "Lex Admin";
$layout_mode = 'admin';

require_once $root . '/hn_core/layout/header.php';
?>

<main class="page admin-page">

<header class="page-header">
    <h1>Lex – Kontrollpanel</h1>
    <p class="page-intro">
        Administrasjon av oppslag, grammatikk og forklaringer
    </p>
</header>

<section class="admin-card-section">
    <div class="admin-course-grid">

        <div class="admin-course-card">
            <h3>Substantiv</h3>
            <p>Inline redigering av kjønn og bøyning.</p>
            <a href="/hn_lex/admin/nouns_list.php" class="hn-btn">
                Åpne
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Alle oppslag</h3>
            <p>Rediger lemma og ordklasse.</p>
            <a href="/hn_lex/admin/entries_list.php" class="hn-btn">
                Åpne
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Forklaringer</h3>
            <p>Rediger nivåforklaringer og eksempler.</p>
            <a href="/hn_lex/admin/explanations_list.php" class="hn-btn">
                Åpne
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Kvalitetskontroll</h3>
            <p>Oppdag inkonsistente kjønn og bøyninger.</p>
            <a href="/hn_lex/admin/quality_nouns.php" class="hn-btn">
                Åpne
            </a>
        </div>

        <div class="admin-course-card">
            <h3>Dokumentasjon</h3>
            <p>Arkitektur og pipeline.</p>
            <a href="/hn_lex/docs_index.php" class="hn-btn">
                Åpne
            </a>
        </div>

    </div>
</section>

</main>

<?php require_once $root . '/hn_core/layout/footer.php'; ?>