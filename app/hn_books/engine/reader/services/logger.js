export function createLogger() {

  function info(...args) {
    console.log("[HN]", ...args);
  }

  function warn(...args) {
    console.warn("[HN]", ...args);
  }

  function error(...args) {
    console.error("[HN]", ...args);
  }

  async function postJSON(url, data = {}) {

    try {

      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
      });

      return await res.json();

    } catch (err) {

      console.warn("Logger postJSON failed", err);
      return null;

    }

  }

  return {
    info,
    warn,
    error,
    postJSON
  };

}