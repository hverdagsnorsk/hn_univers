(() => {
  "use strict";

  const API_BASE = "/hn_admin/api";

  function el(tag, attrs = {}, ...children) {
    const e = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
      if (k === "class") e.className = v;
      else if (k.startsWith("data-")) e.setAttribute(k, v);
      else if (k === "html") e.innerHTML = v;
      else e.setAttribute(k, String(v));
    }
    for (const c of children) {
      if (c === null || c === undefined) continue;
      if (typeof c === "string") e.appendChild(document.createTextNode(c));
      else e.appendChild(c);
    }
    return e;
  }

  function qs(sel, root = document) { return root.querySelector(sel); }

  function jsonFetch(url, opts = {}) {
    return fetch(url, {
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      ...opts
    }).then(async r => {
      const t = await r.text();
      let j = null;
      try { j = JSON.parse(t); } catch { /* ignore */ }
      if (!r.ok) {
        const msg = (j && j.error) ? j.error : `HTTP ${r.status}`;
        throw new Error(msg);
      }
      return j;
    });
  }

  function getStoredParticipant(textId) {
    const key = `hn_tasks_participant_${textId}`;
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return null;
      const obj = JSON.parse(raw);
      if (!obj || !obj.name || !obj.email) return null;
      return obj;
    } catch {
      return null;
    }
  }

  function storeParticipant(textId, data) {
    const key = `hn_tasks_participant_${textId}`;
    localStorage.setItem(key, JSON.stringify(data));
  }

  async function ensureAttempt(textId) {
    let p = getStoredParticipant(textId);
    if (!p) {
      p = await promptParticipant(textId);
      storeParticipant(textId, p);
    }

    const res = await jsonFetch(`${API_BASE}/attempt_start.php`, {
      method: "POST",
      body: JSON.stringify({ text_id: textId, name: p.name, email: p.email })
    });

    return { attempt_id: res.attempt_id, participant: p };
  }

  function promptParticipant(textId) {
    return new Promise((resolve) => {
      const root = qs("#task-root");
      root.innerHTML = "";

      const wrap = el("div", { class: "wrapper" },
        el("div", { class: "hdr" },
          el("h1", {}, "Start oppgaver"),
          el("div", { class: "meta" }, `Tekst-ID: ${textId}`)
        ),
        el("div", { class: "notice" },
          "Skriv inn navn og e-post. Dette brukes til å lagre gjennomføringen."
        )
      );

      const nameInput = el("input", { type: "text", placeholder: "Navn" });
      const emailInput = el("input", { type: "text", placeholder: "E-post" });

      const msg = el("div", { class: "feedback" }, "");

      const btn = el("button", { class: "primary" }, "Start");
      btn.addEventListener("click", () => {
        const name = nameInput.value.trim();
        const email = emailInput.value.trim();
        if (!name) { msg.className = "feedback bad"; msg.textContent = "Du må skrive navn."; return; }
        if (!email || !email.includes("@")) { msg.className = "feedback bad"; msg.textContent = "Du må skrive en gyldig e-post."; return; }
        resolve({ name, email });
      });

      wrap.appendChild(el("div", { class: "card" },
        el("h2", {}, "Deltaker"),
        el("div", { class: "options" },
          nameInput,
          emailInput
        ),
        el("div", { class: "actions" }, btn),
        msg
      ));

      root.appendChild(wrap);
    });
  }

  function renderLayout(titleText, metaText) {
    const root = qs("#task-root");
    root.innerHTML = "";
    const wrap = el("div", { class: "wrapper" },
      el("div", { class: "hdr" },
        el("h1", {}, titleText),
        el("div", { class: "meta" }, metaText)
      )
    );
    root.appendChild(wrap);
    return wrap;
  }

  function getPrompt(payload) {
    return (payload && typeof payload.prompt === "string") ? payload.prompt : "";
  }

  function setFeedback(card, ok, text) {
    let fb = qs(".feedback", card);
    if (!fb) {
      fb = el("div", { class: "feedback" }, "");
      card.appendChild(fb);
    }
    fb.className = ok ? "feedback ok" : "feedback bad";
    fb.textContent = text || "";
  }

  // -------- Renderere per type --------

  function renderMCQ(task, attemptId) {
    const payload = task.payload || {};
    const prompt = getPrompt(payload) || "Velg riktig svar:";
    const options = Array.isArray(payload.options) ? payload.options : [];

    const card = el("div", { class: "card", "data-task-id": task.id });

    card.appendChild(el("h2", {}, `${task.label} (ID ${task.id})`));
    card.appendChild(el("p", { class: "prompt" }, prompt));

    const box = el("div", { class: "options" });
    const name = `mcq_${task.id}`;
    options.forEach((opt, idx) => {
      const radio = el("input", { type: "radio", name, value: String(idx) });
      const lab = el("label", { class: "option" }, radio, el("span", {}, String(opt)));
      box.appendChild(lab);
    });

    const btn = el("button", { class: "primary" }, "Sjekk");
    btn.addEventListener("click", async () => {
      const checked = qs(`input[name="${name}"]:checked`, card);
      const val = checked ? checked.value : null;

      try {
        const res = await jsonFetch(`${API_BASE}/response_submit.php`, {
          method: "POST",
          body: JSON.stringify({ attempt_id: attemptId, task_id: task.id, response: val === null ? null : Number(val) })
        });
        setFeedback(card, res.is_correct === 1, res.feedback);
      } catch (e) {
        setFeedback(card, false, e.message);
      }
    });

    card.appendChild(box);
    card.appendChild(el("div", { class: "actions" }, btn));
    return card;
  }

  function renderFill(task, attemptId) {
    const payload = task.payload || {};
    const prompt = getPrompt(payload) || "Skriv inn riktig ord:";
    const input = el("input", { type: "text", placeholder: "Skriv svaret her" });

    const card = el("div", { class: "card", "data-task-id": task.id });
    card.appendChild(el("h2", {}, `${task.label} (ID ${task.id})`));
    card.appendChild(el("p", { class: "prompt" }, prompt));
    card.appendChild(input);

    const btn = el("button", { class: "primary" }, "Sjekk");
    btn.addEventListener("click", async () => {
      const val = input.value.trim();
      try {
        const res = await jsonFetch(`${API_BASE}/response_submit.php`, {
          method: "POST",
          body: JSON.stringify({ attempt_id: attemptId, task_id: task.id, response: val })
        });
        setFeedback(card, res.is_correct === 1, res.feedback);
      } catch (e) {
        setFeedback(card, false, e.message);
      }
    });

    card.appendChild(el("div", { class: "actions" }, btn));
    return card;
  }

  function renderShort(task, attemptId) {
    const payload = task.payload || {};
    const prompt = getPrompt(payload) || "Svar kort:";
    const input = el("textarea", { placeholder: "Skriv svaret ditt her" });

    const card = el("div", { class: "card", "data-task-id": task.id });
    card.appendChild(el("h2", {}, `${task.label} (ID ${task.id})`));
    card.appendChild(el("p", { class: "prompt" }, prompt));
    card.appendChild(input);

    const btn = el("button", { class: "primary" }, "Send svar");
    btn.addEventListener("click", async () => {
      const val = input.value.trim();
      try {
        const res = await jsonFetch(`${API_BASE}/response_submit.php`, {
          method: "POST",
          body: JSON.stringify({ attempt_id: attemptId, task_id: task.id, response: val })
        });
        // short kan være auto eller ikke – vi viser uansett feedback fra API
        setFeedback(card, res.is_correct === 1, res.feedback);
      } catch (e) {
        setFeedback(card, false, e.message);
      }
    });

    card.appendChild(el("div", { class: "actions" }, btn));
    return card;
  }

  function renderDragSort(task, attemptId) {
    const payload = task.payload || {};
    const prompt = getPrompt(payload) || "Sorter i riktig rekkefølge:";
    const items = Array.isArray(payload.items) ? payload.items : [];

    const card = el("div", { class: "card", "data-task-id": task.id });
    card.appendChild(el("h2", {}, `${task.label} (ID ${task.id})`));
    card.appendChild(el("p", { class: "prompt" }, prompt));

    // Enkel, robust UI: dropdown per posisjon (ikke ekte drag'n'drop).
    // Sender et array med indekser som representerer rekkefølgen.
    const selects = [];
    const list = el("div", { class: "options" });

    for (let pos = 0; pos < items.length; pos++) {
      const sel = el("select", {});
      sel.appendChild(el("option", { value: "" }, `Velg element for plass ${pos + 1}`));
      items.forEach((it, idx) => {
        sel.appendChild(el("option", { value: String(idx) }, String(it)));
      });
      selects.push(sel);
      list.appendChild(sel);
    }

    const btn = el("button", { class: "primary" }, "Sjekk rekkefølge");
    btn.addEventListener("click", async () => {
      const order = selects.map(s => s.value === "" ? null : Number(s.value));
      if (order.some(v => v === null)) {
        setFeedback(card, false, "Du må velge et element på alle plassene.");
        return;
      }
      // Sjekk duplikater
      const set = new Set(order);
      if (set.size !== order.length) {
        setFeedback(card, false, "Du har valgt samme element flere ganger. Velg unike elementer.");
        return;
      }

      try {
        const res = await jsonFetch(`${API_BASE}/response_submit.php`, {
          method: "POST",
          body: JSON.stringify({ attempt_id: attemptId, task_id: task.id, response: order })
        });
        setFeedback(card, res.is_correct === 1, res.feedback);
      } catch (e) {
        setFeedback(card, false, e.message);
      }
    });

    card.appendChild(list);
    card.appendChild(el("div", { class: "actions" }, btn));
    return card;
  }

  function renderTask(task, attemptId) {
    const t = task.task_type;

    if (t === "mcq") return renderMCQ(task, attemptId);
    if (t === "fill_text") return renderFill(task, attemptId);
    if (t === "short") return renderShort(task, attemptId);
    if (t === "drag_sort") return renderDragSort(task, attemptId);

    // fallback
    const card = el("div", { class: "card" });
    card.appendChild(el("h2", {}, `${task.label} (ID ${task.id})`));
    card.appendChild(el("div", { class: "notice" },
      `Oppgavetypen "${t}" er ikke støttet i task_engine.js ennå.`
    ));
    return card;
  }

  async function boot() {
    const root = qs("#task-root");
    if (!root) return;

    const textId = Number(root.dataset.textId || root.getAttribute("data-text-id") || 0);
    if (!textId) {
      root.textContent = "Mangler data-text-id på #task-root.";
      return;
    }

    // Opprett layout
    const wrap = renderLayout("Oppgaver", `Tekst-ID: ${textId}`);

    try {
      const { attempt_id, participant } = await ensureAttempt(textId);

      // Oppdater meta-linje
      qs(".meta", wrap).textContent = `Tekst-ID: ${textId} | ${participant.name} (${participant.email}) | Attempt: ${attempt_id}`;

      // Hent tasks
      const data = await jsonFetch(`${API_BASE}/tasks.php?text_id=${textId}&status=approved`);
      const tasks = (data && data.tasks) ? data.tasks : [];

      if (!tasks.length) {
        wrap.appendChild(el("div", { class: "notice" },
          "Ingen godkjente oppgaver for denne teksten ennå. Gå til hn_admin og godkjenn oppgaver (status=approved)."
        ));
        return;
      }

      // Render tasks
      tasks.forEach(task => {
        wrap.appendChild(renderTask(task, attempt_id));
      });

    } catch (e) {
      wrap.appendChild(el("div", { class: "notice" }, `Feil: ${e.message}`));
    }
  }

  document.addEventListener("DOMContentLoaded", boot);
})();
