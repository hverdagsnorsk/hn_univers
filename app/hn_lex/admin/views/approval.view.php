<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Lex Approval</title>
</head>
<body>

<h1>Approval</h1>

<?php if (!empty($message)) echo "<p>$message</p>"; ?>
<?php if (!empty($error)) echo "<p style='color:red'>$error</p>"; ?>

<table border="1">
<tr><th>ID</th><th>Lemma</th><th>Class</th><th>Action</th></tr>

<?php foreach ($rows as $r): ?>
<tr>
<td><?= $r['id'] ?></td>
<td><?= htmlspecialchars($r['lemma']) ?></td>
<td><?= htmlspecialchars($r['word_class']) ?></td>
<td>
<form method="post">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<button name="action" value="approve">Approve</button>
<button name="action" value="reject">Reject</button>
</form>
</td>
</tr>
<?php endforeach; ?>

</table>

</body>
</html>