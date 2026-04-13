<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN LEX – LEGACY WORKER (DISABLED)
|--------------------------------------------------------------------------
| Denne fila er deaktivert fordi den bruker LexGenerationService
| (gammel AI-pipeline) som skaper feil data i lex_entries.
|
| Ny pipeline:
| - LookupService
| - SenseGenerationService
| - OpenAiLexService
|
| Denne worker skal IKKE brukes.
|--------------------------------------------------------------------------
*/

http_response_code(410);

echo "[LEX] Legacy worker is DISABLED.\n";
echo "[LEX] Reason: Uses deprecated LexGenerationService.\n";
echo "[LEX] Use LookupService pipeline instead.\n";

exit(1);