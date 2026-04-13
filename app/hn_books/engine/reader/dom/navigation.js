export function initNavigation(reader) {

  if (!reader) return;

  reader.querySelectorAll(".reader-nav").forEach(nav => {

    if (nav.dataset.ready) return;
    nav.dataset.ready = "1";

    const back = nav.dataset.back;
    if (!back) return;

    const a = document.createElement("a");
    a.href = back;
    a.className = "reader-back-link";
    a.textContent = "← Tilbake til oversikt";

    nav.appendChild(a);

  });

}