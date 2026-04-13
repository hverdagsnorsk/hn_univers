<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$page_title = "Språkprofil per kurs";
require_once __DIR__ . '/../hn_core/layout/header.php';

/* --------------------------------------------------
   Tilgjengelige språk i systemet
-------------------------------------------------- */
$availableLanguages = [
    'en' => 'Engelsk',
    'hr' => 'Kroatisk',
    'lt' => 'Litauisk',
    'pl' => 'Polsk',
    'lv' => 'Latvisk',
    'uk' => 'Ukrainsk',
    'bg' => 'Bulgarsk',
    'so' => 'Somali',
    'th' => 'Thai',
    'ti' => 'Tigrinja',
    'tl' => 'Tagalog',
    'ur' => 'Urdu',
    'ro' => 'Rumensk'
];

/* --------------------------------------------------
   Hent kurs (fra texts)
-------------------------------------------------- */
$courses = db()->query("
    SELECT DISTINCT book_key
    FROM texts
    ORDER BY book_key
")->fetchAll(PDO::FETCH_COLUMN);

$course = $_GET['course'] ?? ($courses[0] ?? null);

if (!$course) {
    exit("Ingen kurs funnet.");
}

/* --------------------------------------------------
   Hent profil
-------------------------------------------------- */
$stmt = db()->prepare("
    SELECT id, include_ipa
    FROM course_language_profiles
    WHERE course_key = :c
");
$stmt->execute(['c' => $course]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$profileId = $profile['id'] ?? null;
$includeIpa = (int)($profile['include_ipa'] ?? 1);

/* --------------------------------------------------
   Hent aktive språk
-------------------------------------------------- */
$activeLanguages = [];

if ($profileId) {
    $stmt = db()->prepare("
        SELECT language_code
        FROM course_languages
        WHERE profile_id = :pid
        ORDER BY position
    ");
    $stmt->execute(['pid' => $profileId]);
    $activeLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* --------------------------------------------------
   POST – lagre
-------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selected = $_POST['languages'] ?? [];
    $ipa = isset($_POST['include_ipa']) ? 1 : 0;

    db()->beginTransaction();

    try {

        // Opprett eller oppdater profil
        if (!$profileId) {
            $stmt = db()->prepare("
                INSERT INTO course_language_profiles (course_key, include_ipa)
                VALUES (:c, :ipa)
            ");
            $stmt->execute([
                'c' => $course,
                'ipa' => $ipa
            ]);
            $profileId = (int)db()->lastInsertId();
        } else {
            $stmt = db()->prepare("
                UPDATE course_language_profiles
                SET include_ipa = :ipa
                WHERE id = :id
            ");
            $stmt->execute([
                'ipa' => $ipa,
                'id' => $profileId
            ]);

            db()->prepare("
                DELETE FROM course_languages
                WHERE profile_id = :id
            ")->execute(['id' => $profileId]);
        }

        // Lagre språk
        $pos = 1;
        foreach ($selected as $code) {
            $stmt = db()->prepare("
                INSERT INTO course_languages
                (profile_id, language_code, position)
                VALUES (:pid, :code, :pos)
            ");
            $stmt->execute([
                'pid' => $profileId,
                'code' => $code,
                'pos' => $pos++
            ]);
        }

        db()->commit();
        header("Location: ?course=" . urlencode($course));
        exit;

    } catch (Throwable $e) {
        db()->rollBack();
        echo "<p style='color:red'>Feil: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<h2>Språkprofil for kurs: <?= htmlspecialchars($course) ?></h2>

<form method="get" style="margin-bottom:20px;">
    <select name="course" onchange="this.form.submit()">
        <?php foreach ($courses as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $c === $course ? 'selected' : '' ?>>
                <?= htmlspecialchars($c) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>

<form method="post">

    <label>
        <input type="checkbox" name="include_ipa" <?= $includeIpa ? 'checked' : '' ?>>
        Vis IPA
    </label>

    <h3>Velg språk (rekkefølge = visningsrekkefølge)</h3>

    <?php foreach ($availableLanguages as $code => $label): ?>
        <div>
            <label>
                <input type="checkbox"
                       name="languages[]"
                       value="<?= $code ?>"
                       <?= in_array($code, $activeLanguages, true) ? 'checked' : '' ?>>
                <?= htmlspecialchars($label) ?>
            </label>
        </div>
    <?php endforeach; ?>

    <br>
    <button type="submit">Lagre</button>
</form>

<?php
require_once __DIR__ . '/../hn_core/layout/footer.php';
