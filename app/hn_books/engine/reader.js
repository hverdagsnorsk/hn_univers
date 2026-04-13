/* ============================================================
   HN_BOOKS – READER.JS (PRODUCTION SAFE + LEX ENABLED 2026)
   - SAFE DOM PROCESSING (preserves <strong>/<em>/lists)
   - 3-STEP INTERACTION MODEL (sentence -> word -> popup)
   - hn_lex lookup enabled (with context)
   - Logging enabled (non-blocking)
   - [[LYD]] support (TreeWalker text marker split)
   - Popup scale controls
============================================================ */

document.addEventListener("DOMContentLoaded", () => {

  const reader = document.querySelector(".reader");
  if (!reader) return;

  /* ============================================================
     NAVIGATION
  ============================================================ */

  reader.querySelectorAll(".reader-nav").forEach(nav => {
    if (nav.dataset.ready) return;
    nav.dataset.ready = "1";

    const back = nav.dataset.back;
    if (!back) return;

    const a = document.createElement("a");
    a.href = back;
    a.textContent = "Tilbake til oversikt";
    nav.appendChild(a);
  });

  /* ============================================================
     FONT SCALE
  ============================================================ */

  const MIN_SCALE = 1;
  const MAX_SCALE = 2.8;
  const STEP = 0.2;

  let scale = parseFloat(localStorage.getItem("hn_reader_scale") || "1");
  document.documentElement.style.setProperty("--font-scale", scale);

  reader.addEventListener("click", e => {
    const btn = e.target.closest("[data-action]");
    if (!btn) return;

    switch (btn.dataset.action) {
      case "increase":
        scale = Math.min(MAX_SCALE, +(scale + STEP).toFixed(2));
        break;
      case "decrease":
        scale = Math.max(MIN_SCALE, +(scale - STEP).toFixed(2));
        break;
      case "reset":
        scale = 1;
        break;
      default:
        return;
    }

    document.documentElement.style.setProperty("--font-scale", scale);
    localStorage.setItem("hn_reader_scale", scale);
  });

  /* ============================================================
     POPUP SCALE
  ============================================================ */

  let popupScale = parseFloat(localStorage.getItem("hn_popup_scale") || "1");
  document.documentElement.style.setProperty("--popup-scale", popupScale);

  function adjustPopupScale(direction) {
    if (direction === "increase")
      popupScale = Math.min(1.8, +(popupScale + 0.1).toFixed(2));
    if (direction === "decrease")
      popupScale = Math.max(0.8, +(popupScale - 0.1).toFixed(2));

    document.documentElement.style.setProperty("--popup-scale", popupScale);
    localStorage.setItem("hn_popup_scale", popupScale);
  }

  /* ============================================================
     MANUAL AUDIO MARKER SUPPORT [[LYD]] (SAFE)
  ============================================================ */

  function processAudioMarkers() {
    const walker = document.createTreeWalker(reader, NodeFilter.SHOW_TEXT);
    const nodes = [];

    while (walker.nextNode()) {
      const n = walker.currentNode;
      if (n?.nodeValue && n.nodeValue.includes("[[LYD]]")) {
        nodes.push(n);
      }
    }

    nodes.forEach(node => {
      const parts = node.nodeValue.split("[[LYD]]");
      const frag = document.createDocumentFragment();

      parts.forEach((part, i) => {
        if (part) {
          frag.appendChild(document.createTextNode(part));
        }
        if (i < parts.length - 1) {
          const wrap = document.createElement("div");
          wrap.className = "reader-audio-manual";
          wrap.innerHTML = `<button class="reader-audio-btn">🔊 Spill av</button>`;
          frag.appendChild(wrap);
        }
      });

      node.replaceWith(frag);
    });
  }

  processAudioMarkers();

  /* ============================================================
     POPOVER SETUP
  ============================================================ */

  const popover =
    document.getElementById("word-popover") ||
    (() => {
      const p = document.createElement("div");
      p.id = "word-popover";
      p.className = "word-popover hidden";
      p.innerHTML = `
        <div class="lex-popup">

          <div class="lex-popup-controls">
            <button data-popup="decrease">A−</button>
            <button data-popup="increase">A+</button>
          </div>

          <div class="lex-term" id="wp-word">–</div>

          <div class="lex-section hidden" id="wp-class-wrap">
            <div class="lex-heading">Ordklasse</div>
            <div class="lex-text" id="wp-class"></div>
          </div>

          <div class="lex-section hidden" id="wp-grammar-wrap">
            <div class="lex-heading">Bøyning</div>
            <div class="lex-text" id="wp-grammar"></div>
          </div>

          <div class="lex-section">
            <div class="lex-heading">Forklaring</div>
            <div class="lex-text" id="wp-expl"></div>
          </div>

          <div class="lex-section hidden" id="wp-example-wrap">
            <div class="lex-heading">Eksempel</div>
            <div class="lex-text" id="wp-example"></div>
          </div>

        </div>
      `;
      document.body.appendChild(p);
      return p;
    })();

  const popWord = popover.querySelector("#wp-word");
  const popExpl = popover.querySelector("#wp-expl");
  const popExample = popover.querySelector("#wp-example");
  const popExampleWrap = popover.querySelector("#wp-example-wrap");
  const popGrammar = popover.querySelector("#wp-grammar");
  const popGrammarWrap = popover.querySelector("#wp-grammar-wrap");
  const popClass = popover.querySelector("#wp-class");
  const popClassWrap = popover.querySelector("#wp-class-wrap");

  popover.addEventListener("click", e => {
    const btn = e.target.closest("[data-popup]");
    if (!btn) return;
    adjustPopupScale(btn.dataset.popup);
  });

  let activeSentence = null;
  let activeWord = null;
  let currentLookupId = 0;

  function hidePopover() {
    popover.classList.add("hidden");
  }

  /* ============================================================
     POSITION ENGINE
  ============================================================ */

  function positionPopover(wordEl) {
    const margin = 16;
    const rect = wordEl.getBoundingClientRect();

    popover.style.left = "0px";
    popover.style.top = "0px";
    popover.classList.remove("hidden");

    const popRect = popover.getBoundingClientRect();

    let left = rect.left + window.scrollX;
    let top = rect.bottom + window.scrollY + margin;

    if (left + popRect.width > window.scrollX + window.innerWidth - margin) {
      left = window.scrollX + window.innerWidth - popRect.width - margin;
    }

    if (left < window.scrollX + margin) {
      left = window.scrollX + margin;
    }

    if (rect.bottom + popRect.height + margin > window.innerHeight) {
      top = rect.top + window.scrollY - popRect.height - margin;
    }

    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
  }

  /* ============================================================
     LOOKUP (UPDATED FOR CONTEXT)
  ============================================================ */

  async function lookupWord(word, contextObj) {

  const params = new URLSearchParams({
    word: word,
    lang: "no",
    level: "A2",
    prev: contextObj?.prev || "",
    next: contextObj?.next || "",
    sentence: contextObj?.sentence || "",
    paragraph: contextObj?.paragraph || ""   
});

    try {
      const res = await fetch(`/hn_lex/api/lookup.php?${params.toString()}`);
      return await res.json();
    } catch {
      return { found: false };
    }
  }

  async function logLexClick(word, found) {
    try {
      await fetch('/hn_lex/admin/ajax/log_click.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          word: word,
          language: 'no',
          page: window.location.pathname,
          found: found ? 1 : 0
        })
      });
    } catch {}
  }

  /* ============================================================
     SHOW POPOVER (UPDATED FOR CONTEXT)
  ============================================================ */

  function normalizeWord(raw) {
    return (raw || "")
      .replace(/[.,!?;:()«»"]/g, "")
      .trim()
      .toLowerCase();
  }

  function normalizeWordClass(data) {
    const wc =
      data.word_class ??
      data.wordClass ??
      data.ordklasse ??
      data.word_class_value ??
      "";

    if (!wc) return "";
    return String(wc).replace(/^.*:\s*/, "").trim();
  }

  const grammarLabelMap = {
    singular_indefinite: "Ubestemt entall",
    singular_definite: "Bestemt entall",
    plural_indefinite: "Ubestemt flertall",
    plural_definite: "Bestemt flertall",
    infinitive: "Infinitiv",
    present: "Presens",
    past: "Preteritum",
    perfect: "Presens perfektum",
    passive: "Passiv",
    imperative: "Imperativ",
    positive: "Positiv",
    comparative: "Komparativ",
    superlative: "Superlativ",
    gender: "Kjønn"
  };
  async function showPopover(el) {

    const word = normalizeWord(el.textContent);

    const sentenceText = activeSentence
      ? activeSentence.textContent.trim()
      : "";

    // 🔵 NYTT: hent hele avsnittet for bedre semantisk kontekst
    const paragraphText =
      activeSentence?.closest("p")?.textContent?.trim() ||
      sentenceText;

    const words = activeSentence
      ? Array.from(activeSentence.querySelectorAll(".word"))
      : [];

    const index = words.indexOf(el);

    const contextObj = {
      prev: normalizeWord(words[index - 1]?.textContent || ""),
      next: normalizeWord(words[index + 1]?.textContent || ""),
      sentence: sentenceText,
      paragraph: paragraphText // 🔵 NYTT FELT
    };

    const lookupId = ++currentLookupId;

    popWord.textContent = word || "–";
    popExpl.textContent = "Laster forklaring …";

    popClass.textContent = "";
    popClassWrap.classList.add("hidden");

    popGrammar.innerHTML = "";
    popGrammarWrap.classList.add("hidden");

    popExample.textContent = "";
    popExampleWrap.classList.add("hidden");

    popover.classList.remove("hidden");
    positionPopover(el);

    const data = await lookupWord(word, contextObj);
    if (lookupId !== currentLookupId) return;

    if (!data || !data.found) {
      popExpl.textContent = "Dette ordet er ikke forklart ennå.";
      logLexClick(word, false);
      return;
    }

    logLexClick(word, true);

    popExpl.textContent = data.forklaring || "Ingen forklaring tilgjengelig.";

    const wc = normalizeWordClass(data);
    if (wc) {
      popClass.textContent = wc;
      popClassWrap.classList.remove("hidden");
    }

    if (data.example) {
      popExample.textContent = data.example;
      popExampleWrap.classList.remove("hidden");
    }

    if (data.grammar) {

      if (Array.isArray(data.grammar)) {
        const rows = data.grammar
          .filter(r => r && (r.value ?? "").toString().trim() !== "")
          .map(r => `
            <tr>
              <th>${String(r.label || "").trim()}</th>
              <td>${String(r.value || "").trim()}</td>
            </tr>
          `).join("");

        if (rows) {
          popGrammar.innerHTML = `<table class="lex-table">${rows}</table>`;
          popGrammarWrap.classList.remove("hidden");
        }
      }

      if (!Array.isArray(data.grammar) && typeof data.grammar === "object") {
        const rows = Object.entries(data.grammar)
          .filter(([_, v]) => v)
          .map(([k, v]) => `
            <tr>
              <th>${grammarLabelMap[k] || k}</th>
              <td>${String(v).trim()}</td>
            </tr>
          `).join("");

        if (rows) {
          popGrammar.innerHTML = `<table class="lex-table">${rows}</table>`;
          popGrammarWrap.classList.remove("hidden");
        }
      }
    }

    positionPopover(el);
  }

  /* ============================================================
     SAFE DOM PROCESSING (UNCHANGED)
  ============================================================ */

  const SKIP_TAGS = new Set([
    "SCRIPT", "STYLE", "NOSCRIPT",
    "AUDIO", "VIDEO", "SOURCE",
    "BUTTON", "INPUT", "TEXTAREA", "SELECT",
    "CODE", "PRE",
    "NAV"
  ]);

  function isInsideSkippedElement(node) {
    let cur = node.parentNode;
    while (cur && cur !== reader) {
      if (cur.nodeType === Node.ELEMENT_NODE) {
        const tag = cur.tagName;
        if (SKIP_TAGS.has(tag)) return true;
        if (cur.classList?.contains("reader-audio-manual")) return true;
        if (cur.id === "word-popover") return true;
      }
      cur = cur.parentNode;
    }
    return false;
  }

  function processTextNode(node) {
    const text = node.nodeValue;
    if (!text || !text.trim()) return;

    const sentences = text.match(/[^.!?]+[.!?]*/g);
    if (!sentences) return;

    const frag = document.createDocumentFragment();

    sentences.forEach(s => {

      const sSpan = document.createElement("span");
      sSpan.className = "sentence";

      const parts = s.match(/\S+|\s+/g) || [];

      parts.forEach(part => {
        if (/^\s+$/.test(part)) {
          sSpan.appendChild(document.createTextNode(part));
        } else {
          const w = document.createElement("span");
          w.className = "word";
          w.textContent = part;
          sSpan.appendChild(w);
        }
      });

      frag.appendChild(sSpan);
      frag.appendChild(document.createTextNode(" "));
    });

    node.replaceWith(frag);
  }

  function walk(node) {
    if (node.nodeType === Node.ELEMENT_NODE && /^H[1-6]$/.test(node.tagName)) return;

    if (node.nodeType === Node.TEXT_NODE) {
      if (isInsideSkippedElement(node)) return;
      processTextNode(node);
      return;
    }

    if (node.nodeType === Node.ELEMENT_NODE) {
      if (SKIP_TAGS.has(node.tagName)) return;
      if (node.classList?.contains("reader-audio-manual")) return;
      if (node.id === "word-popover") return;

      Array.from(node.childNodes).forEach(child => walk(child));
    }
  }

  reader.querySelectorAll("main section").forEach(section => walk(section));

  /* ============================================================
     INTERACTION (UNCHANGED)
  ============================================================ */

  let clickStage = 0;
  let lastWordEl = null;

  reader.addEventListener("click", e => {

    if (popover.contains(e.target)) return;

    const target =
      e.target.nodeType === Node.TEXT_NODE
        ? e.target.parentElement
        : e.target;

    if (!target) return;

    const sentence = target.closest(".sentence");
    if (!sentence) {
      hidePopover();
      clickStage = 0;
      return;
    }

    const word = target.closest(".word");

    if (activeSentence !== sentence) {
      if (activeSentence) activeSentence.classList.remove("active");
      activeSentence = sentence;
      sentence.classList.add("active");

      clickStage = 1;
      lastWordEl = null;
      if (activeWord) activeWord.classList.remove("active");
      activeWord = null;
      hidePopover();
      return;
    }

    if (!word) return;

    if (word !== lastWordEl) {
      if (activeWord) activeWord.classList.remove("active");
      activeWord = word;
      word.classList.add("active");

      lastWordEl = word;
      clickStage = 2;
      hidePopover();
      return;
    }

    clickStage++;
    if (clickStage >= 3) {
      showPopover(word);
      clickStage = 3;
    }
  });

  window.addEventListener("scroll", () => {
    if (activeWord && !popover.classList.contains("hidden")) {
      positionPopover(activeWord);
    }
  });

  window.addEventListener("resize", () => {
    if (activeWord && !popover.classList.contains("hidden")) {
      positionPopover(activeWord);
    }
  });

});