<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>JS Formatter (Prettier)</title>

<!-- Prettier via CDN -->
<script src="https://unpkg.com/prettier@3.2.5/standalone.js"></script>
<script src="https://unpkg.com/prettier@3.2.5/plugins/babel.js"></script>
<script src="https://unpkg.com/prettier@3.2.5/plugins/estree.js"></script>

<style>
body {
    font-family: system-ui, sans-serif;
    margin: 0;
    background: #f4f6f8;
}

header {
    background: #2c3e50;
    color: white;
    padding: 15px;
    font-size: 20px;
}

.container {
    display: flex;
    height: calc(100vh - 60px);
}

.editor {
    flex: 1;
    display: flex;
    flex-direction: column;
}

textarea {
    flex: 1;
    width: 100%;
    border: none;
    padding: 15px;
    font-family: monospace;
    font-size: 14px;
    line-height: 1.5;
}

.toolbar {
    padding: 10px;
    background: #ecf0f1;
    display: flex;
    gap: 10px;
}

button {
    padding: 8px 14px;
    cursor: pointer;
    border: none;
    background: #2ecc71;
    color: white;
}

button.secondary {
    background: #3498db;
}

</style>
</head>
<body>

<header>JS Code Formatter (Prettier)</header>

<div class="container">

    <div class="editor">
        <div class="toolbar">
            <button onclick="formatCode()">Formater kode</button>
            <button class="secondary" onclick="clearCode()">Tøm</button>
            <button class="secondary" onclick="copyCode()">Kopier</button>
        </div>
        <textarea id="input" placeholder="Lim inn JS-kode her..."></textarea>
    </div>

    <div class="editor">
        <div class="toolbar">
            <span>Formatert kode</span>
        </div>
        <textarea id="output" placeholder="Resultat vises her..."></textarea>
    </div>

</div>

<script>
async function formatCode() {
    const code = document.getElementById('input').value;

    try {
        const formatted = await prettier.format(code, {
            parser: "babel",
            plugins: prettierPlugins,
            semi: true,
            singleQuote: true,
            tabWidth: 4,
            printWidth: 100
        });

        document.getElementById('output').value = formatted;

    } catch (e) {
        alert('Formatteringsfeil: ' + e.message);
    }
}

function clearCode() {
    document.getElementById('input').value = '';
    document.getElementById('output').value = '';
}

function copyCode() {
    const output = document.getElementById('output');
    output.select();
    document.execCommand('copy');
}
</script>

</body>
</html>
