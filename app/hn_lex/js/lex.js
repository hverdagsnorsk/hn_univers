document.addEventListener('DOMContentLoaded', () => {
  const text = document.querySelector('.lex-text');
  const explanationBox = document.getElementById('lex-explanation');
  const selectedList = document.getElementById('lex-selected');
  const batchBtn = document.getElementById('lex-batch-explain');

  if (!text || !explanationBox || !selectedList || !batchBtn) return;

  const selectedWords = new Map(); // word → span

  makeWordsClickable(text);

  /* --------------------------------------------------
     Klikk på ord
  -------------------------------------------------- */
  text.addEventListener('click', async (e) => {
    const span = e.target;
    if (!span.classList.contains('lex-word')) return;

    const word = span.dataset.word;
    if (!word) return;

    // Toggle valgt
    if (selectedWords.has(word)) {
      selectedWords.delete(word);
      span.classList.remove('selected');
    } else {
      selectedWords.set(word, span);
      span.classList.add('selected');
    }

    renderSelectedWords(selectedList, selectedWords, batchBtn);

    explanationBox.innerHTML = '<em>Slår opp ord …</em>';

    try {
      const url =
        `/hn_lex/api/lookup.php?word=${encodeURIComponent(word)}`
        + `&lang=no&level=A2`;

      const res = await fetch(url);
      if (!res.ok) throw new Error();

      const data = await res.json();

      if (!data.found) {
        explanationBox.innerHTML = `
          <strong>${escapeHtml(word)}</strong>
          <p><em>Ordet finnes ikke i databasen ennå.</em></p>
        `;
        return;
      }

      explanationBox.innerHTML = renderLexEntry(data);

    } catch {
      explanationBox.innerHTML =
        '<span style="color:#c00">Feil ved oppslag.</span>';
    }
  });

  /* --------------------------------------------------
     Batch-forklaring
  -------------------------------------------------- */
  batchBtn.addEventListener('click', async () => {
    const words = Array.from(selectedWords.keys());
    if (words.length === 0) return;

    explanationBox.innerHTML = '<em>Forklarer valgte ord …</em>';

    try {
      const res = await fetch('/hn_lex/api/batch_explain.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          words,
          lang: 'no'
        })
      });

      if (!res.ok) throw new Error();

      const data = await res.json();

      explanationBox.innerHTML =
        `<strong>Ord behandlet:</strong><ul>` +
        (data.saved || []).map(w => `<li>${escapeHtml(w)}</li>`).join('') +
        `</ul>`;

    } catch {
      explanationBox.innerHTML =
        '<span style="color:#c00">Feil ved batch-forklaring.</span>';
    }
  });
});

/* --------------------------------------------------
   Valgte ord
-------------------------------------------------- */
function renderSelectedWords(list, map, btn) {
  list.innerHTML = '';

  if (map.size === 0) {
    list.innerHTML = '<li><em>Ingen ord valgt</em></li>';
    btn.disabled = true;
    return;
  }

  for (const word of map.keys()) {
    const li = document.createElement('li');
    li.textContent = word;
    list.appendChild(li);
  }

  btn.disabled = false;
}

/* --------------------------------------------------
   Leksikalsk visning (KONTRAKTSTYRT)
-------------------------------------------------- */
function renderLexEntry(d) {
  let html = '';

  html += `<p><small>Grunnform: <strong>${escapeHtml(d.lemma)}</strong></small></p>`;

  if (d.ordklasse_navn) {
    html += `<p><em>${escapeHtml(d.ordklasse_navn)}</em></p>`;
  } else if (d.ordklasse) {
    html += `<p><em>${escapeHtml(d.ordklasse)}</em></p>`;
  }

  /* ---------- Grammar (helt generisk) ---------- */
  if (d.grammar && typeof d.grammar === 'object') {

    const items = Object.entries(d.grammar)
      .filter(([, v]) => v)
      .map(([k, v]) => `${escapeHtml(k)}: ${escapeHtml(v)}`);

    if (items.length) {
      html += `<p><strong>Grammatikk:</strong><br>${items.join(' · ')}</p>`;
    }
  }

  if (d.explanation) {
    html += `<p><strong>Forklaring:</strong> ${escapeHtml(d.explanation)}</p>`;
  }

  if (d.example) {
    html += `<p><em>${escapeHtml(d.example)}</em></p>`;
  }

  return html;
}

/* --------------------------------------------------
   Gjør ord klikkbare
-------------------------------------------------- */
function makeWordsClickable(container) {
  const walker = document.createTreeWalker(container, NodeFilter.SHOW_TEXT);
  const nodes = [];
  while (walker.nextNode()) nodes.push(walker.currentNode);

  nodes.forEach(node => {
    if (!node.nodeValue.trim()) return;
    if (node.parentElement.closest('.lex-word')) return;

    const parts = node.nodeValue.split(/(\s+)/);
    const frag = document.createDocumentFragment();

    parts.forEach(p => {
      if (/^\s+$/.test(p)) {
        frag.appendChild(document.createTextNode(p));
      } else {
        const clean = p.replace(/[^\p{L}\p{N}-]/gu, '');
        if (!clean) {
          frag.appendChild(document.createTextNode(p));
          return;
        }
        const span = document.createElement('span');
        span.className = 'lex-word';
        span.dataset.word = clean.toLowerCase();
        span.textContent = p;
        frag.appendChild(span);
      }
    });

    node.replaceWith(frag);
  });
}

/* --------------------------------------------------
   Utils
-------------------------------------------------- */
function escapeHtml(str) {
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  })[m]);
}
