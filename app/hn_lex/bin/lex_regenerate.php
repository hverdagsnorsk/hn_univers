<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN LEX – LEGACY REGENERATE (DISABLED)
|--------------------------------------------------------------------------
| Denne fila brukte LexGenerationService til å regenerere oppslag.
| Dette fører til feilklassifisering (f.eks. "tidligere" → substantiv).
|
| Filen er derfor deaktivert.
|
| Bruk i stedet:
| - LookupService
| - SenseGenerationService
| - OpenAiLexService
|--------------------------------------------------------------------------
*/

http_response_code(410);

echo "[LEX] lex_regenerate.php er deaktivert.\n";
echo "[LEX] Årsak: Legacy AI pipeline (LexGenerationService).\n";
echo "[LEX] Regenerering må gjøres via ny pipeline.\n";

exit(1);