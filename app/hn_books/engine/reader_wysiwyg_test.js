/* ============================================================
   HN_BOOKS – READER.JS
   SMART + CONTEXT AWARE
   3-STEP INTERACTION MODEL
   + LEX CLICK LOGGING
   + MANUAL AUDIO MARKER SUPPORT [[LYD]]
   + SAFE DOM TEXT PROCESSING
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
   MANUAL AUDIO MARKER [[LYD]]
============================================================ */

  function processAudioMarkers() {

    const walker = document.createTreeWalker(
      reader,
      NodeFilter.SHOW_TEXT,
      null,
      false
    );

    const nodes = [];
    while (walker.nextNode()) {
      if (walker.currentNode.nodeValue.includes("[[LYD]]")) {
        nodes.push(walker.currentNode);
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
          wrap.innerHTML =
            `<button class="reader-audio-btn">🔊 Spill av</button>`;
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
   LOOKUP
============================================================ */

  async function lookupWord(word, context) {
    try {
      const res = await fetch(
        `/hn_lex/api/lookup.php?word=${encodeURIComponent(word)}&context=${encodeURIComponent(context || "")}`
      );
      return await res.json();
    } catch {
      return { found: false };
    }
  }

/* ============================================================
   LOGGING
============================================================ */

  async function logLexClick(word, found) {
    try {
      await fetch('/hn_lex/admin/ajax/log_click.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          word,
          language: 'no',
          page: window.location.pathname,
          found: found ? 1 : 0
        })
      });
    } catch {}
  }

/* ============================================================
   SHOW POPOVER
============================================================ */

  async function showPopover(el) {

    const word = el.textContent.replace(/[.,!?]/g, "").toLowerCase();
    const sentenceText = activeSentence
      ? activeSentence.textContent.trim()
      : "";

    const lookupId = ++currentLookupId;

    popWord.textContent = word;
    popExpl.textContent = "Laster forklaring …";
    popGrammar.innerHTML = "";
    popGrammarWrap.classList.add("hidden");
    popExample.textContent = "";
    popExampleWrap.classList.add("hidden");

    popover.classList.remove("hidden");
    positionPopover(el);

    const data = await lookupWord(word, sentenceText);

    if (lookupId !== currentLookupId) return;

    if (!data.found) {
      popExpl.textContent = "Dette ordet er ikke forklart ennå.";
      logLexClick(word, false);
      return;
    }

    popExpl.textContent =
      data.forklaring || "Ingen forklaring tilgjengelig.";

    logLexClick(word, true);

    if (data.example) {
      popExample.textContent = data.example;
      popExampleWrap.classList.remove("hidden");
    }

    if (data.grammar) {

      const g = data.grammar;

      const rows = Object.entries(g)
        .filter(([k, v]) => v)
        .map(([k, v]) =>
          `<tr><th>${k}</th><td>${v}</td></tr>`
        ).join("");

      if (rows) {
        popGrammar.innerHTML =
          `<table class="lex-table">${rows}</table>`;
        popGrammarWrap.classList.remove("hidden");
      }
    }

    positionPopover(el);
  }

/* ============================================================
   SAFE TEXT PROCESSING
============================================================ */

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

    if (node.nodeType === Node.ELEMENT_NODE &&
        /^H[1-6]$/.test(node.tagName)) return;

    if (node.classList?.contains("reader-audio-manual")) return;

    if (node.nodeType === Node.TEXT_NODE) {
      processTextNode(node);
      return;
    }

    if (node.nodeType === Node.ELEMENT_NODE) {
      Array.from(node.childNodes).forEach(child => walk(child));
    }
  }

  reader.querySelectorAll(".reader-content section")
    .forEach(section => walk(section));

/* ============================================================
   INTERACTION
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
      if (activeSentence)
        activeSentence.classList.remove("active");

      activeSentence = sentence;
      sentence.classList.add("active");

      clickStage = 1;
      lastWordEl = null;
      hidePopover();
      return;
    }

    if (!word) return;

    if (word !== lastWordEl) {
      if (activeWord)
        activeWord.classList.remove("active");

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
    if (activeWord && !popover.classList.contains("hidden"))
      positionPopover(activeWord);
  });

  window.addEventListener("resize", () => {
    if (activeWord && !popover.classList.contains("hidden"))
      positionPopover(activeWord);
  });

});