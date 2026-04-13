import { translateGrammarKey } from "./reader-i18n.js";

export function renderGrammar(grammar) {

  const rows = Object.entries(grammar)
    .filter(([k, v]) => v)
    .map(([k, v]) => {

      const label = translateGrammarKey(k);
      if (!label) return "";

      return `
        <tr>
          <th>${label}</th>
          <td>${v}</td>
        </tr>
      `;
    })
    .join("");

  if (!rows) return "";

  return `<table class="lex-table">${rows}</table>`;
}