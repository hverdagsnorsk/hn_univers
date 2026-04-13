<?php
declare(strict_types=1);

$currentBook = $text['book_key'] ?? '';
$currentLevel = $text['level'] ?? '';
$currentTitle = $text['title'] ?? '';
$currentStatus = $text['status'] ?? 'draft';
$currentContent = $text['content'] ?? '';

if (!is_string($currentContent)) {
    $currentContent = '';
}
?>

<h1><?= isset($text) ? 'Rediger tekst' : 'Ny tekst' ?></h1>

<form method="post" id="genForm" class="editor-layout">

<input type="hidden" name="id" value="<?= htmlspecialchars((string)($text['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="status" id="status" value="<?= htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="raw_html" id="raw_html">

<aside class="editor-sidebar">

    <h3>Bok</h3>
    <select name="book_key_select">
        <option value="">Velg bok</option>
        <?php foreach ($books as $bk): ?>
            <option value="<?= htmlspecialchars($bk) ?>" <?= $bk === $currentBook ? 'selected' : '' ?>>
                <?= htmlspecialchars($bk) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <h3>Nivå</h3>
    <input name="level" value="<?= htmlspecialchars($currentLevel) ?>">

    <h3>Tittel</h3>
    <input name="title" value="<?= htmlspecialchars($currentTitle) ?>">

    <h3>Status</h3>
    <select onchange="setStatus(this.value)">
        <option value="draft" <?= $currentStatus === 'draft' ? 'selected' : '' ?>>Utkast</option>
        <option value="published" <?= $currentStatus === 'published' ? 'selected' : '' ?>>Publisert</option>
    </select>

    <br><br>
    <button type="submit">Lagre</button>

</aside>

<main class="editor-main">
    <textarea id="editor" name="editor"><?= $currentContent ?></textarea>
</main>

</form>

<style>
.editor-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 24px;
}

.editor-sidebar input,
.editor-sidebar select {
    width: 100%;
    margin-bottom: 15px;
    padding: 8px;
}

.editor-main {
    min-width: 0;
}
</style>

<script src="/assets/js/tinymce/tinymce.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE ikke lastet');
        return;
    }

    tinymce.init({
        selector: '#editor',
        height: 520,
        base_url: '/assets/js/tinymce',
        suffix: '.min',
        menubar: 'file edit view insert format tools',
        plugins: 'lists link image table code',
        toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code | audio dialog oppgave fakta',

        setup: function (editor) {
            editor.ui.registry.addButton('audio', {
                text: 'LYD',
                onAction: function () {
                    editor.insertContent('[[LYD]]<p></p>');
                }
            });

            editor.ui.registry.addButton('dialog', {
                text: 'Dialog',
                onAction: function () {
                    editor.insertContent('<div class="dialog"><p><strong>A:</strong> </p><p><strong>B:</strong> </p></div><p></p>');
                }
            });

            editor.ui.registry.addButton('oppgave', {
                text: 'Oppgave',
                onAction: function () {
                    editor.insertContent('<div class="oppgave"><p><strong>Oppgave:</strong></p><p></p></div><p></p>');
                }
            });

            editor.ui.registry.addButton('fakta', {
                text: 'Fakta',
                onAction: function () {
                    editor.insertContent('<div class="faktaboks"><p><strong>Husk:</strong></p><p></p></div><p></p>');
                }
            });
        },

        content_style: `
            body {
                font-family: Arial, sans-serif;
                font-size: 16px;
                line-height: 1.6;
            }

            .dialog {
                background: #f5f7fa;
                padding: 10px;
                border-left: 4px solid #3b82f6;
                margin: 10px 0;
            }

            .oppgave {
                background: #fff7ed;
                padding: 10px;
                border-left: 4px solid #f97316;
                margin: 10px 0;
            }

            .faktaboks {
                background: #ecfdf5;
                padding: 10px;
                border-left: 4px solid #10b981;
                margin: 10px 0;
            }
        `
    });

    document.getElementById('genForm').addEventListener('submit', function (e) {
        if (typeof tinymce === 'undefined') {
            e.preventDefault();
            alert('Editor ikke lastet');
            return;
        }

        var editor = tinymce.get('editor');

        if (!editor) {
            e.preventDefault();
            alert('Editor ikke klar');
            return;
        }

        var html = editor.getContent();
        document.getElementById('raw_html').value = html;

        var textOnly = html.replace(/<[^>]*>/g, '').trim();

        if (!textOnly && html.indexOf('[[LYD]]') === -1) {
            e.preventDefault();
            alert('Tekst mangler');
        }
    });
});

function setStatus(val) {
    document.getElementById('status').value = val;
}
</script>