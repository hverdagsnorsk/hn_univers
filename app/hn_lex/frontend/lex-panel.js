(() => {

  const panel = document.createElement('div');
  panel.className = 'lex-panel';
  panel.innerHTML = `
    <span class="close">×</span>
    <div class="content"></div>
  `;
  document.body.appendChild(panel);

  panel.querySelector('.close').onclick = () => {
    panel.style.display = 'none';
  };

  async function lookup(word) {
    const res = await fetch(
      `/hn_lex/api/public_lookup.php?word=${encodeURIComponent(word)}`
    );
    return res.json();
  }

  function logClick(payload) {
    // Bevisst "fire and forget"
    fetch('/hn_lex/api/log_click.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    }).catch(() => {});
  }

  function render(data) {
    if (!data.found) {
      panel.querySelector('.content').innerHTML =
        `<p>Ingen forklaring funnet.</p>`;
      return;
    }

    let html = `
      <h2>${data.lemma}</h2>
      <div class="label">Ordklasse</div>
      <div>${data.ordklasse}</div>
    `;

    if (data.inflection) {
      html += `<div class="label">Bøyning</div><div class="inflection">`;
      for (const [k, v] of Object.entries(data.inflection)) {
        html += `<div>${k}: ${v}</div>`;
      }
      html += `</div>`;
    }

    if (data.explanation) {
      html += `<div class="label">Forklaring</div><div>${data.explanation}</div>`;
    }

    if (data.example) {
      html += `<div class="label">Eksempel</div><div>${data.example}</div>`;
    }

    panel.querySelector('.content').innerHTML = html;
  }

  document.addEventListener('click', async e => {
    const el = e.target.closest('.lex-word');
    if (!el) return;

    const word = el.dataset.word || el.textContent.trim();
    const data = await lookup(word);

    // LOGGING (statistikk)
    logClick({
      word: word,
      found: data.found ? 1 : 0,
      lemma: data.lemma || null,
      language: 'no',
      page: location.pathname
    });

    render(data);
    panel.style.display = 'block';
  });

})();
