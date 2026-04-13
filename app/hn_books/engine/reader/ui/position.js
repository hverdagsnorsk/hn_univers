export function positionPopover(target, popover) {

    const rect = target.getBoundingClientRect();
    const pop = popover.getBoundingClientRect();

    let top = rect.bottom + 8;
    let left = rect.left;

    // hold innenfor skjerm
    if (left + pop.width > window.innerWidth) {
        left = window.innerWidth - pop.width - 10;
    }

    if (top + pop.height > window.innerHeight) {
        top = rect.top - pop.height - 8;
    }

    if (left < 10) left = 10;
    if (top < 10) top = 10;

    popover.style.position = "fixed";
    popover.style.top = `${top}px`;
    popover.style.left = `${left}px`;
}