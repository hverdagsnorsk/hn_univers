import { initNavigation } from "./dom/navigation.js";
import { initAudioMarkers } from "./dom/audioMarkers.js";
import { processReaderContent } from "./dom/processor.js";

import { initFontScale } from "./ui/fontScale.js";
import { createPopoverController } from "./ui/popover.js";
import { positionPopover } from "./ui/position.js";

import { initInteraction } from "./interaction/interactionEngine.js";

import { createLexService } from "./services/lexService.js";
import { on } from "./core/events.js";

document.addEventListener("DOMContentLoaded", () => {

    console.log("[reader] init");

    const reader = document.querySelector(".reader");

    if (!reader) {
        console.error("[reader] .reader not found");
        return;
    }

    // =========================
    // INIT CORE
    // =========================

    processReaderContent(reader);

    initNavigation(reader);
    initFontScale();
    initAudioMarkers(reader);

    const lex = createLexService();
    const popover = createPopoverController();

    initInteraction({
        reader,
        popover,
        positionEngine: { position: positionPopover }
    });

    // =========================
    // LOOKUP FLOW
    // =========================

    on("popup:shown", async ({ word, sentence }) => {

        console.log("[lookup]", word);

        // Vis loader umiddelbart
        popover.update({
            title: word,
            content: "Laster..."
        });

        try {
            const result = await lex.lookupWord(word, {
                sentence
            });

            console.log("[lookup result]", result);

            if (!result?.found) {
                popover.update({
                    title: result?.query || word,
                    content: "Forklaring kommer snart"
                });
                return;
            }

            popover.update(result);

        } catch (e) {
            console.error("[lookup failed]", e);

            popover.update({
                title: word,
                content: "Kunne ikke hente data"
            });
        }
    });

    // =========================
    // GLOBAL CLOSE HANDLING
    // =========================

    // Lukk ved scroll
    window.addEventListener("scroll", () => {
        if (popover.isVisible()) {
            popover.hide();
        }
    }, { passive: true });

    // ⚠️ Viktig: delay for å ikke ødelegge click-flow
    setTimeout(() => {

        document.addEventListener("click", (e) => {

            // Klikk inne i popup → ikke lukk
            if (popover.element.contains(e.target)) return;

            // Klikk inne i reader → la interactionEngine håndtere
            if (e.target.closest(".reader")) return;

            // Ellers → lukk popup
            if (popover.isVisible()) {
                popover.hide();
            }

        });

    }, 0);

});