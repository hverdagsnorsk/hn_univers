#!/usr/bin/env php
<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN LEX – TEST PIPELINE (DISABLED)
|--------------------------------------------------------------------------
| Denne fila brukte LexGenerationService (legacy AI pipeline)
| for å teste generering og lagring.
|
| Dette kan føre til feil data i produksjonsdatabasen.
|
| Test må nå gjøres via:
| - LookupService
| - SenseGenerationService
| - OpenAiLexService
|--------------------------------------------------------------------------
*/

fwrite(STDERR, "[LEX] test_pipeline.php er deaktivert.\n");
fwrite(STDERR, "[LEX] Årsak: Legacy pipeline (LexGenerationService).\n");
fwrite(STDERR, "[LEX] Bruk ny pipeline for testing.\n");

exit(1);