<?php
declare(strict_types=1);

/*
|------------------------------------------------------------
| _helpers/auth.php
| Enkel og eksplisitt autentisering / autorisasjon
|------------------------------------------------------------
*/

/**
 * Sjekk om bruker er admin
 */
function is_admin(): bool
{
    return !empty($_SESSION['admin']) && $_SESSION['admin'] === true;
}

/**
 * Krev admin-tilgang
 * Stopper eksekvering hvis ikke
 */
function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Ingen tilgang');
    }
}

/**
 * Hent admin-identitet (for logging/audit)
 * Returnerer null hvis ikke admin
 */
function current_admin(): ?array
{
    return $_SESSION['admin_user'] ?? null;
}
