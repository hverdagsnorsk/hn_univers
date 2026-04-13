/*
|--------------------------------------------------------------------------
| HN Books – Tasks Engine (Løsning B)
| Felles frontend-motor for alle oppgavetyper
|--------------------------------------------------------------------------
| Støttede typer:
| - mcq
| - fill
| - short
| - order
| - match
|--------------------------------------------------------------------------
*/

(function () {

  /* ==========================================================
     HELPERS
     ========================================================== */

  const qs  = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

  const norm = v => (v ?? '').toString().trim().toLowerCase();

  function setFeedback(taskEl, ok, msg) {
    const fb = qs('.task-feedback', taskEl);
    if (!fb) return;

    fb.hidden = false;
    fb.classList.remove('ok', 'bad');
    fb.classList.add(ok ? 'ok' : 'bad');

    const good = qs('.task-feedback-correct', fb);
    const bad  = qs('.task-feedback-incorrect', fb);

    if (good) good.style.display = ok ? 'block' : 'none';
    if (bad)  bad.style.display  = ok ? 'none'  : 'block';

    if (ok && good) good.textContent = msg;
    if (!ok && bad) bad.textContent = msg;
  }

  /* ==========================================================
     MCQ
     ========================================================== */

  function initMCQ(taskEl) {
    const correct = parseInt(taskEl.dataset.correctIndex, 10);
    const btn = qs('[data-action="check"]', taskEl);

    if (!btn || !Number.isFinite(correct)) return;

    btn.addEventListener('click', () => {
      const sel = qs('input[type="radio"]:checked', taskEl);
      if (!sel) {
        setFeedback(taskEl, false, 'Velg et alternativ først.');
        return;
      }

      const val = parseInt(sel.value, 10);
      setFeedback(
        taskEl,
        val === correct,
        val === correct ? 'Riktig.' : 'Feil.'
      );
    });
  }

  /* ==========================================================
     FILL
     ========================================================== */

  function initFill(taskEl) {
    const answer = taskEl.dataset.answer ?? '';
    const btn = qs('[data-action="check"]', taskEl);
    const input = qs('input[type="text"]', taskEl);

    if (!btn || !input) return;

    btn.addEventListener('click', () => {
      if (norm(input.value) === '') {
        setFeedback(taskEl, false, 'Skriv et svar først.');
        return;
      }

      const ok = norm(input.value) === norm(answer);
      setFeedback(
        taskEl,
        ok,
        ok ? 'Riktig.' : 'Feil. Riktig svar: ' + answer
      );
    });
  }

  /* ==========================================================
     SHORT
     ========================================================== */

  function initShort(taskEl) {
    const btn = qs('[data-action="send"]', taskEl);
    const area = qs('textarea', taskEl);

    if (!btn || !area) return;

    btn.addEventListener('click', () => {
      if (norm(area.value) === '') {
        setFeedback(taskEl, false, 'Skriv et svar først.');
        return;
      }

      setFeedback(taskEl, true, 'Svaret er sendt inn.');
    });
  }

  /* ==========================================================
     ORDER
     ========================================================== */

  function initOrder(taskEl) {
    const correct = JSON.parse(taskEl.dataset.correctOrder ?? '[]');
    const btn = qs('[data-action="check"]', taskEl);

    if (!btn || correct.length < 2) return;

    btn.addEventListener('click', () => {
      const rows = qsa('.order-item', taskEl);

      const pairs = rows.map(row => {
        const input = qs('input', row);
        return {
          idx: parseInt(input.value, 10),
          word: row.dataset.word
        };
      });

      if (pairs.some(p => !Number.isFinite(p.idx))) {
        setFeedback(taskEl, false, 'Fyll inn tall på alle linjer.');
        return;
      }

      pairs.sort((a, b) => a.idx - b.idx);
      const candidate = pairs.map(p => p.word);

      let ok = candidate.length === correct.length;
      for (let i = 0; i < candidate.length && ok; i++) {
        if (candidate[i] !== correct[i]) ok = false;
      }

      setFeedback(
        taskEl,
        ok,
        ok ? 'Riktig rekkefølge.' : 'Feil rekkefølge. Prøv igjen.'
      );
    });
  }

  /* ==========================================================
     MATCH
     ========================================================== */

  function initMatch(taskEl) {
    const correctMap = JSON.parse(taskEl.dataset.correctMap ?? '{}');
    const left  = qsa('.match-left-item', taskEl);
    const right = qsa('.match-right-item', taskEl);
    const btn   = qs('[data-action="check"]', taskEl);

    let dragged = null;

    right.forEach(item => {
      item.addEventListener('dragstart', () => {
        dragged = item;
        item.classList.add('dragging');
      });
      item.addEventListener('dragend', () => {
        dragged = null;
        item.classList.remove('dragging');
      });
    });

    left.forEach(target => {
      target.addEventListener('dragover', e => e.preventDefault());
      target.addEventListener('drop', () => {
        if (dragged) target.appendChild(dragged);
      });
    });

    if (!btn) return;

    btn.addEventListener('click', () => {
      let ok = true;

      left.forEach(l => {
        const key = l.dataset.left;
        const r   = qs('.match-right-item', l);
        if (!r || norm(r.textContent) !== norm(correctMap[key])) {
          ok = false;
        }
      });

      setFeedback(
        taskEl,
        ok,
        ok ? 'Alle par er riktige.' : 'Noen par er feil. Prøv igjen.'
      );
    });
  }

  /* ==========================================================
     INIT
     ========================================================== */

  function initTasks() {
    qsa('.task').forEach(taskEl => {
      const type = taskEl.dataset.taskType;

      switch (type) {
        case 'mcq':   initMCQ(taskEl);   break;
        case 'fill':  initFill(taskEl);  break;
        case 'short': initShort(taskEl); break;
        case 'order': initOrder(taskEl); break;
        case 'match': initMatch(taskEl); break;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', initTasks);

})();
