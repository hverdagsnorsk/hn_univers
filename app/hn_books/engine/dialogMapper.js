// /hn_books/engine/dialogMapper.js

export function initDialogMapping() {

  const dialogs = document.querySelectorAll('.dialog');

  dialogs.forEach(dialog => {

    const lines = dialog.querySelectorAll('p');

    const speakerMap = new Map();
    let speakerIndex = 0;

    const MAX_SPEAKERS = 6;

    lines.forEach(line => {

      const strong = line.querySelector('strong');

      if (!strong) return;

      let name = strong.textContent.trim();

      // Fjern kolon
      name = name.replace(':', '').trim();

      if (!speakerMap.has(name)) {
        speakerMap.set(name, speakerIndex % MAX_SPEAKERS);
        speakerIndex++;
      }

      const id = speakerMap.get(name);

      line.classList.add('speaker-' + id);

    });

  });

}