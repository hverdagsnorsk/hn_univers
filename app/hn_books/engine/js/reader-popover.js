import { t } from "./reader-i18n.js";
import { renderGrammar } from "./reader-grammar.js";

export function createPopover() {

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
        <div class="lex-heading">${t("grammar")}</div>
        <div class="lex-text" id="wp-grammar"></div>
      </div>

      <div class="lex-section">
        <div class="lex-heading">${t("explanation")}</div>
        <div class="lex-text" id="wp-expl"></div>
      </div>

      <div class="lex-section hidden" id="wp-example-wrap">
        <div class="lex-heading">${t("example")}</div>
        <div class="lex-text" id="wp-example"></div>
      </div>

    </div>
  `;

  document.body.appendChild(p);
  return p;
}

export function fillPopover(popover, data) {

  const popWord = popover.querySelector("#wp-word");
  const popExpl = popover.querySelector("#wp-expl");
  const popGrammar = popover.querySelector("#wp-grammar");
  const popGrammarWrap = popover.querySelector("#wp-grammar-wrap");
  const popExample = popover.querySelector("#wp-example");
  const popExampleWrap = popover.querySelector("#wp-example-wrap");

  popWord.textContent = data.word;

  if (!data.found) {
    popExpl.textContent = t("not_found");
    return;
  }

  popExpl.textContent =
    data.forklaring || t("no_explanation");

  if (data.example) {
    popExample.textContent = data.example;
    popExampleWrap.classList.remove("hidden");
  }

  if (data.grammar) {
    const html = renderGrammar(data.grammar);
    if (html) {
      popGrammar.innerHTML = html;
      popGrammarWrap.classList.remove("hidden");
    }
  }
}