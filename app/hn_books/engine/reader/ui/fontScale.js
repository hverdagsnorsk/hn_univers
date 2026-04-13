import { MIN_FONT_SCALE, MAX_FONT_SCALE, FONT_STEP } from '../core/config.js';

export function initFontScale(reader) {

  if (!reader || typeof reader.addEventListener !== "function") {
    console.warn("fontScale: invalid reader container");
    return;
  }

  /* ===============================
     INIT SCALE
  =============================== */

  let scale = parseFloat(localStorage.getItem("hn_reader_scale") || "1");

  if (isNaN(scale)) scale = 1;

  scale = Math.max(MIN_FONT_SCALE, Math.min(MAX_FONT_SCALE, scale));

  document.documentElement.style.setProperty("--font-scale", scale);

  /* ===============================
     CLICK HANDLER
  =============================== */

  reader.addEventListener("click", e => {

    const btn = e.target.closest(".reader-tools [data-action]");
    if (!btn) return;

    switch (btn.dataset.action) {

      case "increase":
        scale = Math.min(
          MAX_FONT_SCALE,
          +(scale + FONT_STEP).toFixed(2)
        );
        break;

      case "decrease":
        scale = Math.max(
          MIN_FONT_SCALE,
          +(scale - FONT_STEP).toFixed(2)
        );
        break;

      case "reset":
        scale = 1;
        break;

      default:
        return;

    }

    document.documentElement.style.setProperty("--font-scale", scale);

    try {
      localStorage.setItem("hn_reader_scale", scale);
    } catch {}

  });

}