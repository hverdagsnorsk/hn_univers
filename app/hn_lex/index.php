<?php
declare(strict_types=1);

/*
|------------------------------------------------------------------
| hn_lex/index.php
|------------------------------------------------------------------
| - HN Lex Admin Launchpad (HN-2026)
|------------------------------------------------------------------
*/

$root = dirname(__DIR__);

require_once $root . '/hn_core/inc/bootstrap.php';
require_once $root . '/hn_core/auth/admin.php';

$layout_mode = 'admin';
$page_title  = 'HN Lex';

/*
|------------------------------------------------------------------
| HEADER (HN Core Layout)
|------------------------------------------------------------------
*/
require_once $root . '/hn_core/layout/header.php';
?>

<section class="hn-section">
  <div class="hn-container">

    <header class="hn-pagehead">
      <h1 class="hn-title">
        <?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <p class="hn-lead">
        Kjapp inngang til det du faktisk bruker.
      </p>
    </header>

    <div class="hn-grid hn-grid--cards hn-mb-4">

      <article class="hn-card">
        <div class="hn-card__body">
          <h2 class="hn-card__title">Queue Monitor</h2>
          <p class="hn-meta">Status på AI-jobber.</p>
          <p class="hn-mt-3">
            <a class="hn-btn hn-btn--primary"
               href="admin/queue_monitor.php">
               Åpne
            </a>
          </p>
        </div>
      </article>

      <article class="hn-card">
        <div class="hn-card__body">
          <h2 class="hn-card__title">Alle oppslag</h2>
          <p class="hn-meta">Søk, filtrer og åpne oppslag.</p>
          <p class="hn-mt-3">
            <a class="hn-btn hn-btn--primary"
               href="admin/list.php">
               Åpne
            </a>
          </p>
        </div>
      </article>

      <article class="hn-card">
        <div class="hn-card__body">
          <h2 class="hn-card__title">Click stats</h2>
          <p class="hn-meta">Click Analytics / manglende ord.</p>
          <p class="hn-mt-3">
            <a class="hn-btn hn-btn--primary"
               href="admin/click_stats.php">
               Åpne
            </a>
          </p>
        </div>
      </article>

    </div>

    <article class="hn-card">
      <div class="hn-card__body">

        <h2 class="hn-card__title">Hurtig redigering</h2>

        <form id="lex-quick-form"
              class="hn-form hn-mt-3"
              action="admin/list.php"
              method="get"
              autocomplete="off">

          <div class="hn-row hn-row--gap">

            <div class="hn-field hn-field--grow" style="position:relative;">
              <label class="hn-label" for="lex-q">Lemma</label>

              <input
                class="hn-input"
                id="lex-q"
                name="q"
                placeholder="Søk lemma…"
                aria-autocomplete="list"
                aria-expanded="false"
                aria-controls="lex-suggest"
              >

              <div
                id="lex-suggest"
                class="hn-card"
                style="display:none; position:absolute; left:0; right:0; top:calc(100% + 8px); z-index:50;"
              >
                <div class="hn-card__body" style="padding:0.5rem;">
                  <div id="lex-suggest-items"></div>
                  <div id="lex-suggest-empty"
                       class="hn-meta"
                       style="display:none; padding:0.5rem;">
                    Ingen treff.
                  </div>
                </div>
              </div>
            </div>

            <div class="hn-field hn-field--actions">
              <button class="hn-btn hn-btn--primary"
                      type="submit">
                Gå
              </button>
              <a class="hn-btn"
                 href="admin/list.php">
                Avansert søk
              </a>
            </div>

          </div>
        </form>

        <div class="hn-actions hn-mt-3">
          <a class="hn-btn"
             href="admin/inconsistent_nouns.php">
            Inkonsistente substantiv
          </a>
        </div>

      </div>
    </article>

  </div>
</section>

<script src="/hn_lex/js/lex_index.js?v=2026-1" defer></script>

<?php
/*
|------------------------------------------------------------------
| FOOTER (HN Core Layout)
|------------------------------------------------------------------
*/
require_once $root . '/hn_core/layout/footer.php';