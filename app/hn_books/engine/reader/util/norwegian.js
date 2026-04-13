export function normalizeWord(raw) {

  if (!raw) return "";

  return raw
    .toLowerCase()
    .replace(/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/gu, "")
    .trim();
}


export function guessLemma(word) {

  if (!word) return word;

  const w = normalizeWord(word);

  /* plural nouns */

  if (w.endsWith("ene")) return w.slice(0,-3);
  if (w.endsWith("ene")) return w.slice(0,-3);
  if (w.endsWith("er")) return w.slice(0,-2);
  if (w.endsWith("ene")) return w.slice(0,-3);
  if (w.endsWith("a")) return w.slice(0,-1);

  /* definite nouns */

  if (w.endsWith("et")) return w.slice(0,-2);
  if (w.endsWith("en")) return w.slice(0,-2);

  /* verbs */

  if (w.endsWith("te")) return w.slice(0,-2);
  if (w.endsWith("de")) return w.slice(0,-2);
  if (w.endsWith("et")) return w.slice(0,-2);

  return w;
}