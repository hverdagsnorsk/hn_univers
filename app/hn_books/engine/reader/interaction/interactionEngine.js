import { STAGE } from '../core/state.js';
import { emit } from '../core/events.js';

export function initInteraction({ reader, state = {}, popover, positionEngine = null }) {

  state.clickStage = STAGE.NONE;

  function clearSentence() {
    if (state.activeSentence) {
      state.activeSentence.classList.remove("active");
    }
    state.activeSentence = null;
  }

  function clearWord() {
    if (state.activeWord) {
      state.activeWord.classList.remove("active");
    }
    state.activeWord = null;
  }

  reader.addEventListener("click", e => {

    const target =
      e.target.nodeType === Node.TEXT_NODE
        ? e.target.parentElement
        : e.target;

    if (!target) return;

    const sentence = target.closest(".sentence");

    // RESET
    if (!sentence) {
      popover.hide();
      state.clickStage = STAGE.NONE;
      clearSentence();
      clearWord();
      state.lastWordEl = null;
      return;
    }

    const word = target.closest(".word");

    // 1️⃣ SENTENCE
    if (state.activeSentence !== sentence) {

      clearSentence();
      clearWord();

      sentence.classList.add("active");

      state.activeSentence = sentence;
      state.clickStage = STAGE.SENTENCE;
      state.lastWordEl = null;

      popover.hide();

      return;
    }

    if (!word) return;

    // 2️⃣ WORD
    if (word !== state.lastWordEl) {

      clearWord();

      word.classList.add("active");

      state.activeWord = word;
      state.lastWordEl = word;

      state.clickStage = STAGE.WORD;

      popover.hide();

      return;
    }

    // 3️⃣ POPUP
    if (state.clickStage === STAGE.WORD) {

      state.clickStage = STAGE.POPUP;

      try {
        if (positionEngine && positionEngine.position) {
          popover.show(word, positionEngine.position);
        } else {
          popover.show(word); // 🔥 fallback
        }
      } catch (err) {
        console.error("SHOW ERROR:", err);
        popover.show(word); // 🔥 fallback
      }

      // 🔥 ALLTID FYR EVENT
      emit("popup:shown", {
        word: word.innerText,
        sentence: state.activeSentence?.innerText || ""
      });
    }

  });

}