<?php
declare(strict_types=1);

// 🔥 LOAD APP (JUSTER PATH OM NØDVENDIG)
require_once __DIR__ . '/../../../hn_core/inc/bootstrap.php';

// Hvis bootstrap ikke finnes, prøv:
// require_once __DIR__ . '/../../../hn_core/inc/init.php';
// require_once __DIR__ . '/../../../config.php';

$pdo = db('courses');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SAVE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($_POST['resources'] as $id => $data) {

        $courseId = $data['course_id'] !== '' ? (int)$data['course_id'] : null;
        $isShared = isset($data['is_shared']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE hn_resources
            SET course_id = :course_id,
                is_shared = :is_shared
            WHERE id = :id
        ");

        $stmt->execute([
            'course_id' => $courseId,
            'is_shared' => $isShared,
            'id'        => (int)$id
        ]);
    }

    echo "<p style='color:green;'>Lagret!</p>";
}

// FETCH
$stmt = $pdo->query("
    SELECT id, title, stored_filename, course_id, is_shared
    FROM hn_resources
    ORDER BY id DESC
");

$resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Bulk edit dokumenter</h2>

<form method="post">

<table border="1" cellpadding="6" cellspacing="0">

<tr>
    <th>ID</th>
    <th>Navn</th>
    <th>Fil</th>
    <th>Course ID</th>
    <th>Felles</th>
</tr>

<?php foreach ($resources as $r): ?>

<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= htmlspecialchars($r['title'] ?? '') ?></td>
    <td><?= htmlspecialchars($r['stored_filename'] ?? '') ?></td>

    <td>
        <input 
            type="number" 
            name="resources[<?= $r['id'] ?>][course_id]" 
            value="<?= $r['course_id'] ?? '' ?>" 
            style="width:80px;"
        >
    </td>

    <td style="text-align:center;">
        <input 
            type="checkbox" 
            name="resources[<?= $r['id'] ?>][is_shared]" 
            <?= $r['is_shared'] ? 'checked' : '' ?>
        >
    </td>
</tr>

<?php endforeach; ?>

</table>

<br>

<button style="padding:10px 20px;">💾 Lagre alle</button>

</form>