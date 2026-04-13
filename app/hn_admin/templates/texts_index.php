<h1>Texts</h1>

<table border="1" cellpadding="6">
    <tr>
        <th>ID</th>
        <th>Title</th>
        <th>Book</th>
        <th>Key</th>
        <th>Active</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($texts as $t): ?>
        <tr>
            <td><?= $t['id'] ?></td>
            <td><?= htmlspecialchars($t['title']) ?></td>
            <td><?= $t['book_key'] ?></td>
            <td><?= $t['text_key'] ?></td>
            <td><?= $t['active'] ? 'YES' : 'NO' ?></td>
            <td>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button name="action" value="toggle">Toggle</button>
                </form>

                <form method="post" style="display:inline;">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button name="action" value="delete" onclick="return confirm('Delete?')">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>