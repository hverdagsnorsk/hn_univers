// /hn_books/engine/reader/dom/audioMarkers.js

export function initAudioMarkers(reader = document) {
    console.log("[audioMarkers] init");
    processAudioMarkers(reader);
}

export function processAudioMarkers(reader) {
    const walker = document.createTreeWalker(reader, NodeFilter.SHOW_TEXT);
    const nodes = [];

    while (walker.nextNode()) {
        const n = walker.currentNode;
        if (n?.nodeValue && n.nodeValue.includes("[[LYD]]")) {
            nodes.push(n);
        }
    }

    nodes.forEach(node => {
        const parts = node.nodeValue.split("[[LYD]]");
        const frag = document.createDocumentFragment();

        parts.forEach((part, i) => {
            if (part) frag.appendChild(document.createTextNode(part));

            if (i < parts.length - 1) {
                const wrap = document.createElement("div");
                wrap.className = "reader-audio-manual";
                wrap.innerHTML = `<button class="reader-audio-btn">🔊 Spill av</button>`;
                frag.appendChild(wrap);
            }
        });

        node.replaceWith(frag);
    });
}