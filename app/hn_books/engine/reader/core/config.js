export const MIN_FONT_SCALE = 1;
export const MAX_FONT_SCALE = 2.8;
export const FONT_STEP = 0.2;

export const MIN_POPUP_SCALE = 0.8;
export const MAX_POPUP_SCALE = 1.8;
export const POPUP_STEP = 0.1;

export const SKIP_TAGS = new Set([
  "SCRIPT", "STYLE", "NOSCRIPT",
  "AUDIO", "VIDEO", "SOURCE",
  "BUTTON", "INPUT", "TEXTAREA", "SELECT",
  "CODE", "PRE",
  "NAV"
]);