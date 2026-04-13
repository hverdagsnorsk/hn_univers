(() => {

  const input   = document.getElementById('lex-q');
  const form    = document.getElementById('lex-quick-form');
  const box     = document.getElementById('lex-suggest');
  const itemsEl = document.getElementById('lex-suggest-items');
  const emptyEl = document.getElementById('lex-suggest-empty');

  if (!input) return;

  let timer = null;
  let activeIndex = -1;
  let suggestions = [];

  const endpoint = 'admin/ajax/lex_suggest.php';

  function showBox() {
    box.style.display = 'block';
    input.setAttribute('aria-expanded', 'true');
  }

  function hideBox() {
    box.style.display = 'none';
    input.setAttribute('aria-expanded', 'false');
    activeIndex = -1;
  }

  function clearBox() {
    itemsEl.innerHTML = '';
    emptyEl.style.display = 'none';
    suggestions = [];
    activeIndex = -1;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#039;'
    }[c]));
  }

  function render(list) {

    clearBox();

    if (!Array.isArray(list) || list.length === 0) {
      emptyEl.style.display = 'block';
      showBox();
      return;
    }

    suggestions = list;

    list.forEach((row, idx) => {

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'hn-btn';
      btn.style.display = 'block';
      btn.style.width = '100%';
      btn.style.textAlign = 'left';
      btn.style.margin = '0';
      btn.style.borderRadius = '10px';
      btn.style.padding = '0.55rem 0.6rem';

      const meta = [];
      if (row.pos) meta.push(row.pos);
      if (row.id)  meta.push('#' + row.id);

      btn.innerHTML = `
        <div style="display:flex; justify-content:space-between;">
          <div style="font-weight:600;">
            ${escapeHtml(row.lemma || '')}
          </div>
          <div class="hn-meta">
            ${escapeHtml(meta.join(' · '))}
          </div>
        </div>
        ${row.preview
          ? `<div class="hn-meta" style="margin-top:0.2rem;">
               ${escapeHtml(row.preview)}
             </div>`
          : ''}
      `;

      btn.addEventListener('click', () => {
        if (!row.id) return;
        window.location.href =
          `admin/entry_editor.php?id=${encodeURIComponent(row.id)}`;
      });

      itemsEl.appendChild(btn);
    });

    showBox();
  }

  async function fetchSuggest(q) {
    const url =
      `${endpoint}?q=${encodeURIComponent(q)}&limit=10`;

    const res = await fetch(url, { credentials: 'same-origin' });
    if (!res.ok) return [];
    const data = await res.json();
    return Array.isArray(data) ? data : [];
  }

  input.addEventListener('input', () => {

    const q = input.value.trim();

    if (timer) clearTimeout(timer);

    if (q.length < 2) {
      hideBox();
      return;
    }

    timer = setTimeout(async () => {
      try {
        const list = await fetchSuggest(q);
        render(list);
      } catch {
        hideBox();
      }
    }, 150);
  });

  document.addEventListener('click', (e) => {
    if (!box.contains(e.target) && e.target !== input)
      hideBox();
  });

})();