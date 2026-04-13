document.addEventListener("DOMContentLoaded", () => {

    const modal = document.getElementById("hn-preview-modal");
    if (!modal) return;

    const body      = modal.querySelector("#hn-preview-body");
    const backdrop  = modal.querySelector(".hn-modal-backdrop");
    const closeBtn  = modal.querySelector(".hn-modal-close");

    let activeRequest = null;

    function openModal() {
        modal.classList.remove("hidden");
        document.body.style.overflow = "hidden";
    }

    function closeModal() {
        modal.classList.add("hidden");
        document.body.style.overflow = "";
    }

    async function loadPreview(id) {

        if (!id) return;

        // Cancel previous request if still pending
        if (activeRequest && activeRequest.abort) {
            activeRequest.abort();
        }

        const controller = new AbortController();
        activeRequest = controller;

        body.innerHTML = "Laster…";
        openModal();

        try {
            const res = await fetch("ajax/preview.php?id=" + encodeURIComponent(id), {
                signal: controller.signal
            });

            if (!res.ok) {
                throw new Error("Network error");
            }

            body.innerHTML = await res.text();

        } catch (err) {

            if (err.name === "AbortError") return;

            body.innerHTML = "Kunne ikke laste forhåndsvisning.";
        }
    }

    /* =========================================
       EVENT DELEGATION (ROBUST)
    ========================================= */

    document.addEventListener("click", function (e) {

        const previewLink = e.target.closest(".hn-preview-link");
        if (previewLink) {
            e.preventDefault();
            loadPreview(previewLink.dataset.id);
            return;
        }

        if (e.target.closest(".hn-modal-close") ||
            e.target.closest(".hn-modal-backdrop")) {
            closeModal();
            return;
        }
    });

    /* =========================================
       ESC TO CLOSE
    ========================================= */

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && !modal.classList.contains("hidden")) {
            closeModal();
        }
    });

});
