<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| HN_ADMIN – Global konfigurasjon
|--------------------------------------------------------------------------
*/

/* ============================================================
   AUTENTISERING (midlertidig / enkel admin)
   ============================================================ */

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'passord123');

/* ============================================================
   OPENAI / AI
   ============================================================ */

/*
 * OpenAI API-nøkkel
 * NB: Skal ALDRI eksponeres i frontend eller versjonskontroll
 */
'openai_key' => $_ENV['OPENAI_API_KEY'] ?? ''

/* ============================================================
   BASISSTIER
   ============================================================ */

/**
 * Roten til www/
 * Eksempel: /home/7/h/hverdagsnorsk/www
 *
 * NB: _config ligger i /hn_admin/_config
 * Derfor må vi TO nivåer opp
 */
define('WWW_ROOT', realpath(__DIR__ . '/../..'));

/**
 * Admin-rot
 * /www/hn_admin
 */
define('HN_ADMIN_ROOT', WWW_ROOT . '/hn_admin');

/**
 * Bøker (innhold + lesemotor)
 * /www/hn_books
 */
define('HN_BOOKS_ROOT', WWW_ROOT . '/hn_books');

/**
 * Bøkenes innhold
 * /www/hn_books/books
 */
define('HN_BOOKS_CONTENT', HN_BOOKS_ROOT . '/books');

/* ============================================================
   ADMIN-DATA (IKKE INNHOLD)
   ============================================================ */

define('ADMIN_DATA', HN_ADMIN_ROOT . '/data');

define('TASKS_FILE',      ADMIN_DATA . '/tasks.json');
define('TASK_LINKS_FILE', ADMIN_DATA . '/task_links.json');
define('ADMIN_META_FILE', ADMIN_DATA . '/admin_meta.json');

/* ============================================================
   HJELPEFUNKSJONER (JSON)
   ============================================================ */

function read_json(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }

    $data = json_decode((string)file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_json(string $file, array $data): void
{
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }

    file_put_contents(
        $file,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/* ============================================================
   HJELPERE: BØKER OG TEKSTER (filbasert)
   ============================================================ */

/**
 * Returnerer alle book_key-mapper under /hn_books/books
 */
function get_books(): array
{
    if (!is_dir(HN_BOOKS_CONTENT)) {
        return [];
    }

    return array_values(array_filter(
        scandir(HN_BOOKS_CONTENT),
        fn($d) =>
            $d !== '.' &&
            $d !== '..' &&
            is_dir(HN_BOOKS_CONTENT . '/' . $d)
    ));
}

/**
 * Returnerer HTML-tekster for én bok (brukes kun der DB ikke er i spill)
 */
function get_texts_for_book(string $book): array
{
    $path = HN_BOOKS_CONTENT . '/' . $book . '/texts';

    if (!is_dir($path)) {
        return [];
    }

    $files = glob($path . '/*.html') ?: [];

    return array_map(
        fn($file) => [
            'id'   => basename($file, '.html'),
            'path' => $file,
        ],
        $files
    );
}
