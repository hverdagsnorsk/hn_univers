<h1><?= h($course['title']) ?> – Tidsplan</h1>

<h2>Nytt tidspunkt</h2>

<form method="post" action="?action=event_store&course_id=<?= $course['id'] ?>">

    <label>Start</label>
    <input type="datetime-local" name="start_datetime" required>

    <label>Slutt</label>
    <input type="datetime-local" name="end_datetime">

    <label>Sted</label>
    <input type="text" name="location">

    <label>Møte-lenke</label>
    <input type="text" name="meeting_url">

    <label>Summary</label>
    <input type="text" name="summary">

    <label>Beskrivelse</label>
    <textarea name="description"></textarea>

    <button class="hn-btn">Legg til</button>
</form>

<hr>

<h2>Eksisterende</h2>

<table class="hn-table">
    <tr>
        <th>Start</th>
        <th>Sted</th>
        <th></th>
    </tr>

    <?php foreach ($events as $e): ?>
        <tr>
            <td><?= h($e['start_datetime']) ?></td>
            <td><?= h($e['location']) ?></td>
            <td>
                <a href="?action=event_delete&course_id=<?= $course['id'] ?>&id=<?= $e['id'] ?>"
                   onclick="return confirm('Slette?')">Slett</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>