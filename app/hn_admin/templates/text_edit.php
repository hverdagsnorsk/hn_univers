<h1>Rediger tekst</h1>

<form method="post">
    <input type="text" name="title" value="<?= htmlspecialchars($text['title']) ?>">
    <textarea name="content"><?= htmlspecialchars($text['content']) ?></textarea>
    <button>Lagre</button>
</form>
