<!DOCTYPE html>
<html lang="no">
<head>
<meta charset="UTF-8">
<title>Lex Admin</title>

<style>
body {
    font-family: Arial;
    background: #f5f5f5;
    padding: 20px;
}

h1 {
    margin-bottom: 20px;
}

.card {
    background: white;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

button {
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.approve {
    background: green;
    color: white;
}

</style>
</head>

<body>

<h1>Pending ord</h1>

<div id="list"></div>

<script>

async function loadPending() {

    const res = await fetch('/hn_lex/api/admin/pending.php');
    const data = await res.json();

    const list = document.getElementById('list');
    list.innerHTML = '';

    data.forEach(item => {

        const div = document.createElement('div');
        div.className = 'card';

        div.innerHTML = `
            <div>
                <strong>${item.lemma}</strong><br>
                <small>ID: ${item.id}</small>
            </div>
            <button class="approve" onclick="approve(${item.id})">
                Godkjenn
            </button>
        `;

        list.appendChild(div);
    });
}

async function approve(id) {

    await fetch('/hn_lex/api/admin/approve.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'entry_id=' + id
    });

    loadPending();
}

loadPending();

</script>

</body>
</html>