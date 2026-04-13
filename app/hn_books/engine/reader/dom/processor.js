// /hn_books/engine/reader/dom/processor.js

export function processReaderContent(root) {

    const paragraphs = root.querySelectorAll("main p");

    paragraphs.forEach(p => {

        if (p.dataset.processed) return;

        const text = p.textContent.trim();

        const sentences = text.match(/[^.!?]+[.!?]?/g) || [];

        p.innerHTML = sentences.map(sentence => {

            const words = sentence.match(/[\p{L}\-]+|[^\p{L}\s]+/gu) || [];

            return `<span class="sentence">` +
                words.map(w => {
                    if (/^[\p{L}\-]+$/u.test(w)) {
                        return `<span class="word">${w}</span>`;
                    }
                    return w;
                }).join(" ") +
                `</span>`;

        }).join(" ");

        p.dataset.processed = "1";

    });
}