<?php ob_start(); ?>

<h2>Ressurser</h2>

<form method="post" action="?action=upload" enctype="multipart/form-data">
    <input type="file" name="file" required>
    <button>Last opp</button>
</form>

<hr>

<?php foreach ($resources as $r): ?>
    <div class="card">
        <?= htmlspecialchars($r['original_filename']) ?>
        (<?= $r['resource_type'] ?>)
    </div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
