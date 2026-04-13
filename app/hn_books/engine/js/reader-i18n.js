// ============================================================
// HN I18N SYSTEM (FOUNDATION FOR MULTI-LANGUAGE)
// ============================================================

window.HN_LANG = document.documentElement.lang || "no";

const HN_TRANSLATIONS = {

  no: {
    tilbake: "Tilbake til oversikt",
    loading_explanation: "Laster forklaring …",
    not_found: "Dette ordet er ikke forklart ennå.",
    no_explanation: "Ingen forklaring tilgjengelig.",
    grammar: "Bøyning",
    explanation: "Forklaring",
    example: "Eksempel",

    grammar_labels: {
      infinitive: "Infinitiv",
      present: "Presens",
      past: "Preteritum",
      perfect: "Presens perfektum",
      passive: "Passiv",
      imperative: "Imperativ",
      future: "Futurum",
      participle: "Partisipp",
      comparative: "Komparativ",
      superlative: "Superlativ"
    }
  }

  // senere:
  // en: { ... }
};

export function t(key) {
  return HN_TRANSLATIONS[HN_LANG]?.[key] || key;
}

export function translateGrammarKey(key) {
  return HN_TRANSLATIONS[HN_LANG]?.grammar_labels?.[key] || null;
}