<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

/* ======================================================
   INIT
====================================================== */

$error   = '';
$success = '';

$layout_mode = 'admin';
$page_title  = 'Opprett ny tekst';

/* ======================================================
   HENT BØKER
====================================================== */

$books = get_books();

/* ======================================================
   HJELPERE
====================================================== */

function normalize_key(string $s): string
{
    $s = trim($s);
    $s = preg_replace('/\s+/', '-', $s);
    $s = preg_replace('/[^A-Za-z0-9\-]/', '', $s);
    $s = preg_replace('/\-+/', '-', $s);
    return strtolower(trim($s, '-'));
}

function next_text_key( string $bookKey): string
{
    $stmt = db()->prepare("
        SELECT text_key
        FROM texts
        WHERE book_key = :bk
        ORDER BY id DESC
        LIMIT 200
    ");
    $stmt->execute(['bk' => $bookKey]);

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $max = 0;
    foreach ($rows as $tk) {
        if (preg_match('/-(\d{3})$/', (string)$tk, $m)) {
            $n = (int)$m[1];
            if ($n > $max) $max = $n;
        }
    }

    $next = $max + 1;

    return ucfirst($bookKey) . '-' . str_pad((string)$next, 3, '0', STR_PAD_LEFT);
}

/* ======================================================
   POST
====================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bookKeySelected = trim($_POST['book_key_select'] ?? '');
    $bookKeyNew      = trim($_POST['book_key_new'] ?? '');

    if ($bookKeySelected !== '') {
        $bookKey = normalize_key($bookKeySelected);
    } elseif ($bookKeyNew !== '') {
        $bookKey = normalize_key($bookKeyNew);
    } else {
        $error = 'Du må velge eksisterende bok eller skrive ny.';
    }

    $title   = trim($_POST['title'] ?? '');
    $level   = trim($_POST['level'] ?? '');
    $rawText = trim($_POST['raw_text'] ?? '');

    if (!$error && ($title === '' || $rawText === '')) {
        $error = 'Tittel og tekst må fylles ut.';
    }

    if (!$error) {

        require_once __DIR__ . '/text_generate.php';

        try {

            $textKey = next_text_key($pdo, $bookKey);

            $sourcePath = generate_text_html(
                bookKey: $bookKey,
                textKey: $textKey,
                title:   $title,
                rawText: $rawText,
                level:   ($level !== '' ? $level : null)
            );

            $stmt = db()->prepare("
                INSERT INTO texts (
                    book_key,
                    text_key,
                    title,
                    level,
                    source_path,
                    active,
                    created_at
                ) VALUES (
                    :book_key,
                    :text_key,
                    :title,
                    :level,
                    :source_path,
                    1,
                    NOW()
                )
            ");

            $stmt->execute([
                'book_key'    => $bookKey,
                'text_key'    => $textKey,
                'title'       => $title,
                'level'       => ($level !== '' ? $level : null),
                'source_path' => $sourcePath
            ]);

            $success = "Tekst opprettet: {$sourcePath}";

        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}

/* ======================================================
   LAYOUT
====================================================== */

require_once dirname(__DIR__) . '/hn_core/layout/header.php';
?>

<main class="hn-container hn-admin">

    <section class="hn-hero">
        <div class="hn-hero__content">
            <h1>Opprett ny tekst</h1>
            <p>Velg bok, fyll inn metadata og generer tekst.</p>
        </div>
    </section>

    <?php if ($error !== ''): ?>
        <div class="hn-alert hn-alert--error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="hn-alert hn-alert--success"><?= h($success) ?></div>
    <?php endif; ?>

    <form method="post" class="hn-form-grid" novalidate>

        <!-- BOK -->
        <div class="hn-card">
            <h3>Bok</h3>

            <label>Velg eksisterende bok</label>
            <select name="book_key_select">
                <option value="">Velg eksisterende bok …</option>
                <?php foreach ($books as $bk): ?>
                    <option value="<?= h((string)$bk) ?>">
                        <?= h((string)$bk) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="hn-divider">eller</div>

            <label>Opprett ny bok</label>
            <input name="book_key_new" placeholder="f.eks. consolvo2">
        </div>

        <!-- METADATA -->
        <div class="hn-card">
            <h3>Metadata</h3>

            <div class="hn-form-row">
                <div>
                    <label>Nivå</label>
                    <input name="level" placeholder="A2 / B1 / B2">
                </div>

                <div>
                    <label>Tittel</label>
                    <input name="title" required>
                </div>
            </div>
        </div>

        <!-- TEKST -->
        <div class="hn-card hn-card--full">
            <h3>Tekstinnhold</h3>

            <textarea
                name="raw_text"
                class="hn-editor"
                required
                placeholder="Lim inn teksten her...

Bruk [[LYD]] der du vil starte ny lydfil."
            ></textarea>
        </div>

        <div class="hn-form-actions">
            <button type="submit" class="hn-btn hn-btn--primary hn-btn--large">
                Generer tekst
            </button>
        </div>

    </form>

</main>

<?php require_once dirname(__DIR__) . '/hn_core/layout/footer.php'; ?>