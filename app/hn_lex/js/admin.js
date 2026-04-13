/* ==========================================================
   HN LEX – ADMIN JS
   hn_lex/js/admin.js
========================================================== */

document.addEventListener('DOMContentLoaded', () => {

    const searchInput = document.getElementById('lex-search');

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.toLowerCase();
            const rows = document.querySelectorAll('.lex-row');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(q) ? '' : 'none';
            });
        });
    }

});
