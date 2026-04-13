<h1>Kurs</h1>

<div class="admin-toolbar">
    <a href="/hn_courses/admin/create.php" class="hn-btn primary">
        + Opprett kurs
    </a>
</div>

<div class="admin-card">

    <table class="hn-table">
        <thead>
            <tr>
                <th style="width:60px;">ID</th>
                <th>Tittel</th>
                <th>Slug</th>
                <th style="width:200px;">Handlinger</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($courses as $c): ?>
            <tr>
                <td><?= (int)$c['id'] ?></td>
                <td><?= h($c['title']) ?></td>
                <td class="muted"><?= h($c['slug']) ?></td>

                <td class="actions">
                    <a class="link" href="?action=course_content&course_id=<?= $c['id'] ?>">
                        Rediger
                    </a>

                    <a class="link" href="?action=schedule&course_id=<?= $c['id'] ?>">
                        Tidsplan
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</div>

<style>
.admin-toolbar {
    margin-bottom: 20px;
}

.admin-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.hn-table {
    width: 100%;
    border-collapse: collapse;
}

.hn-table th {
    text-align: left;
    font-size: 14px;
    color: #666;
    border-bottom: 1px solid #ddd;
    padding: 10px;
}

.hn-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
}

.hn-table tr:hover {
    background: #f7f9f9;
}

.muted {
    color: #888;
    font-size: 14px;
}

.actions {
    display: flex;
    gap: 12px;
}

.link {
    color: #2f8485;
    text-decoration: none;
    font-weight: 500;
}

.link:hover {
    text-decoration: underline;
}

.hn-btn.primary {
    background: #2f8485;
    color: white;
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
}
</style>