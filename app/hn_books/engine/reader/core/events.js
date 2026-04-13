const listeners = {};

export function on(event, handler) {
    if (!listeners[event]) {
        listeners[event] = [];
    }
    listeners[event].push(handler);
}

export function emit(event, payload) {
    if (!listeners[event]) return;

    for (const handler of listeners[event]) {
        try {
            handler(payload);
        } catch (e) {
            console.error("[events] handler error", e);
        }
    }
}