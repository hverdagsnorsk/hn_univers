export const STAGE = {
  NONE: 0,
  SENTENCE: 1,
  WORD: 2,
  POPUP: 3
};

export function createState() {
  return {
    activeSentence: null,
    activeWord: null,
    lastWordEl: null,
    clickStage: STAGE.NONE,
    currentLookupId: 0,
    currentWords: null,
    isDestroyed: false
  };
}