export function createPopoverController() {

    const element = document.createElement("div");
    element.className = "word-popover hidden";

    document.body.appendChild(element);

    function show(target, positionFn) {

        element.classList.remove("hidden");

        requestAnimationFrame(() => {
            if (positionFn && target) {
                positionFn(target, element);
            }
        });
    }

    function hide() {
        element.classList.add("hidden");
    }

    function isVisible() {
        return !element.classList.contains("hidden");
    }

    function update(data) {

        if (data?.title || data?.content) {
            element.innerHTML = `
                <div class="lex-popup">
                    <div class="lex-term">${data.title || ""}</div>
                    <div class="lex-text">${data.content || ""}</div>
                </div>
            `;
            return;
        }

        element.innerHTML = `
            <div class="lex-popup">
                <div class="lex-term">${data?.lemma || "Ukjent ord"}</div>
                <div class="lex-heading">${data?.word_class_label || ""}</div>
                <div class="lex-text">${data?.forklaring || "Ingen forklaring tilgjengelig"}</div>
            </div>
        `;
    }

    return {
        element,
        show,
        hide,
        isVisible,
        update
    };
}