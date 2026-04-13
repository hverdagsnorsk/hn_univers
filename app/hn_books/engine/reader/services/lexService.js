export function createLexService(endpoint = "/hn_lex/api/lookup.php") {

    const cache = new Map();

    return {

        async lookupWord(word, context = {}) {

            if (!word) {
                throw new Error("No word provided");
            }

            const key = (
                word.toLowerCase() +
                "|" +
                (context?.sentence || "")
            );

            // =========================
            // CACHE
            // =========================

            if (cache.has(key)) {
                console.log("[cache hit]", key);
                return cache.get(key);
            }

            console.log("[fetch]", word);

            try {

                const url =
                    endpoint +
                    "?word=" + encodeURIComponent(word) +
                    "&sentence=" + encodeURIComponent(context?.sentence || "");

                const res = await fetch(url, {
                    method: "GET"
                });

                if (!res.ok) {
                    throw new Error("HTTP " + res.status);
                }

                const data = await res.json();

                cache.set(key, data);

                return data;

            } catch (err) {

                console.error("[lexService error]", err);

                return {
                    found: false,
                    query: word,
                    forklaring: "Kunne ikke hente data"
                };

            }

        }

    };

}