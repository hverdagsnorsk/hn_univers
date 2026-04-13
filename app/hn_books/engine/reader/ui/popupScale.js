import {
  MIN_POPUP_SCALE,
  MAX_POPUP_SCALE,
  POPUP_STEP
} from '../core/config.js';

export function createPopupScaleController() {

  /* =========================
     INIT SCALE
  ========================= */

  let popupScale = parseFloat(
    localStorage.getItem("hn_popup_scale") || "1"
  );

  if (isNaN(popupScale)) popupScale = 1;

  popupScale = Math.max(
    MIN_POPUP_SCALE,
    Math.min(MAX_POPUP_SCALE, popupScale)
  );

  applyScale();

  /* =========================
     APPLY SCALE
  ========================= */

  function applyScale() {
    document.documentElement.style.setProperty(
      "--popup-scale",
      popupScale
    );
  }

  /* =========================
     ADJUST
  ========================= */

  function adjust(direction) {

    if (direction === "increase") {

      popupScale = Math.min(
        MAX_POPUP_SCALE,
        +(popupScale + POPUP_STEP).toFixed(2)
      );

    }

    else if (direction === "decrease") {

      popupScale = Math.max(
        MIN_POPUP_SCALE,
        +(popupScale - POPUP_STEP).toFixed(2)
      );

    }

    else if (direction === "reset") {

      popupScale = 1;

    }

    applyScale();

    try {
      localStorage.setItem("hn_popup_scale", popupScale);
    } catch {}

  }

  /* =========================
     INIT (optional future hooks)
  ========================= */

  function init() {
    applyScale();
  }

  return {
    init,
    adjust
  };

}