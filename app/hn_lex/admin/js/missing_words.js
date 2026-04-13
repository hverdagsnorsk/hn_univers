const overlay = document.getElementById('overlay');
const buttons = document.querySelectorAll('button');

function lockUI() {
    buttons.forEach(b => b.disabled = true);
    overlay.classList.add('active');
}

function unlockUI() {
    overlay.classList.remove('active');
    buttons.forEach(b => b.disabled = false);
}

/* Merk topp 20 */
document.getElementById('select-top')?.addEventListener('click', () => {
    const boxes = [...document.querySelectorAll('.w')];
    boxes.forEach(cb => cb.checked = false);
    boxes.slice(0, 20).forEach(cb => cb.checked = true);
});

/* Merk alle */
document.getElementById('select-all')?.addEventListener('click', () => {
    document.querySelectorAll('.w').forEach(cb => cb.checked = true);
});

/* Fjern alle */
document.getElementById('deselect-all')?.addEventListener('click', () => {
    document.querySelectorAll('.w').forEach(cb => cb.checked = false);
});

/* Generer */
document.getElementById('generate')?.addEventListener('click', async () => {

    const ids = [...document.querySelectorAll('.w:checked')]
        .map(cb => cb.dataset.id);

    if (!ids.length) {
        alert('Velg minst ett ord');
        return;
    }

    lockUI();

    try {
        const res = await fetch('ajax/generate_missing.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({ ids })
        });

        const json = await res.json();

        alert(
            'Lagret: ' + (json.saved?.length || 0) +
            '\nFeil: ' + Object.keys(json.errors || {}).length
        );

        location.reload();

    } catch {
        alert('Noe gikk galt under generering.');
        unlockUI();
    }
});

/* Slett */
document.getElementById('delete')?.addEventListener('click', async () => {

    const ids = [...document.querySelectorAll('.w:checked')]
        .map(cb => cb.dataset.id);

    if (!ids.length) {
        alert('Velg minst ett ord');
        return;
    }

    if (!confirm('Slette valgte ord fra loggen?')) return;

    lockUI();

    try {
        const res = await fetch('ajax/delete_missing.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({ ids })
        });

        const json = await res.json();

        alert('Slettet: ' + (json.deleted || 0));
        location.reload();

    } catch {
        alert('Noe gikk galt under sletting.');
        unlockUI();
    }
});
