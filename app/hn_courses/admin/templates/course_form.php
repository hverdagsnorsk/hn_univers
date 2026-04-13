<h1><?= isset($course) && $course ? 'Rediger kurs' : 'Opprett kurs' ?></h1>

<?php if (!empty($error)): ?>
    <div class="admin-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post"
      action="?action=<?= isset($course) && $course ? 'course_update&course_id=' . $course['id'] : 'course_store' ?>"
      class="admin-form">

    <div class="form-group">
        <label>Tittel</label>
        <input type="text"
               name="title"
               value="<?= htmlspecialchars($course['title'] ?? '') ?>"
               required>
    </div>

    <div class="form-group">
        <label>Beskrivelse</label>
        <textarea name="description" rows="4"><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
    </div>

    <?php if (isset($course) && $course): ?>
        <div class="form-group">
            <label>Slug</label>
            <input type="text"
                   name="slug"
                   value="<?= htmlspecialchars($course['slug']) ?>">
        </div>
    <?php endif; ?>

    <button type="submit" class="hn-btn">
        <?= isset($course) && $course ? 'Oppdater' : 'Opprett kurs' ?>
    </button>

</form>