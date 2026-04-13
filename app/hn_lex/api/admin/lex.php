<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Lex Admin</title>

<style>
body { font-family: Arial; background: #f5f5f5; padding: 20px; }

.card {
    background: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 10px;
}

h2 { margin: 0 0 10px; }

.section { margin-top: 10px; }

button {
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 5px;
}

.approve { background: green; color: white; }
.reject { background: red; color: white; }

</style>
</head>

<body>

<h1>AI Oppslag (pending)</h1>

<div id="list"></div>

<script>

async function loadPending() {

    const res = await fetch('/hn_lex/api/admin/pending_full.php');
    const data = await res.json();

    const list = document.getElementById('list');
    list.innerHTML = '';

    data.forEach(item => {

        const div = document.createElement('div');
        div.className = 'card';

        div.innerHTML = `
            <h2>${item.lemma} (${item.word_class})</h2>

            <div class="section">
                <strong>Former:</strong><br>
                ${item.forms.join(', ')}
            </div>

            <div class="section">
                <strong>Forklaring:</strong><br>
                ${item.explanation?.explanation ?? ''}
            </div>

            <div class="section">
                <strong>Eksempel:</strong><br>
                ${item.explanation?.example ?? ''}
            </div>

            <div class="section">
                <strong>Grammatikk:</strong><br>
                ${item.grammar.map(g => g.key + ': ' + g.value).join('<br>')}
            </div>

            <div class="section">
                <button class="approve" onclick="approve(${item.id})">Godkjenn</button>
                <button class="reject" onclick="reject(${item.id})">Avvis</button>
            </div>
        `;

        list.appendChild(div);
    });
}

async function approve(id) {
    await fetch('/hn_lex/api/admin/approve.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'entry_id=' + id
    });
    loadPending();
}

async function reject(id) {
    await fetch('/hn_lex/api/admin/reject.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'entry_id=' + id
    });
    loadPending();
}

loadPending();

</script>

</body>
</html>