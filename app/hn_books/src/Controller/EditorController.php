<?php
declare(strict_types=1);

namespace HnBooks\Controller;

use HnBooks\Service\TextService;

class EditorController
{
    public function index(): void
    {
        $service = new TextService();

        $error = '';
        $success = '';
        $text = null;

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        /* ============================================================
           LOAD EXISTING TEXT
        ============================================================ */

        if ($id > 0) {
            $text = $service->getById($id);

            if ($text && !empty($text['source_path'])) {

                // 🔴 FIKS: unngå dobbel /app
                $sourcePath = ltrim($text['source_path'], '/');

                if (str_starts_with($sourcePath, 'app/')) {
                    $fullPath = HN_ROOT . '/' . $sourcePath;
                } else {
                    $fullPath = HN_ROOT . '/app/' . $sourcePath;
                }

                if (file_exists($fullPath)) {
                    $text['content'] = file_get_contents($fullPath);
                } else {
                    error_log('[EDITOR] Missing file: ' . $fullPath);
                    $text['content'] = '';
                }

            } else {
                $text['content'] = '';
            }
        }

        /* ============================================================
           SAVE (DEBUG MODE)
        ============================================================ */

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            try {

                // 🔍 DEBUG: se hva som faktisk sendes
                error_log('[EDITOR POST] ' . print_r($_POST, true));

                if (empty($_POST['raw_html'])) {
                    throw new \Exception('raw_html mangler i POST');
                }

                $htmlLength = strlen((string)$_POST['raw_html']);
                error_log('[EDITOR HTML LENGTH] ' . $htmlLength);

                // 🔥 KALL SAVE
                $path = $service->save($_POST);

                error_log('[EDITOR SAVED PATH] ' . $path);

                $success = "Lagret: " . $path;

            } catch (\Throwable $e) {

                error_log('[EDITOR SAVE ERROR] ' . $e->__toString());
                $error = $e->getMessage();
            }
        }

        /* ============================================================
           LOAD BOOKS
        ============================================================ */

        $books = $this->getBooks();

        /* ============================================================
           VIEW
        ============================================================ */

        $view = HN_ROOT . '/app/hn_books/templates/editor_form.php';

        require HN_ROOT . '/app/hn_core/layout/header.php';

        // 🔴 VIS FEIL TYDELIG I UI
        if (!empty($error)) {
            echo '<div style="background:#fee;padding:10px;border:1px solid red;margin-bottom:10px;">';
            echo '<strong>Feil:</strong> ' . htmlspecialchars($error);
            echo '</div>';
        }

        if (!empty($success)) {
            echo '<div style="background:#efe;padding:10px;border:1px solid green;margin-bottom:10px;">';
            echo htmlspecialchars($success);
            echo '</div>';
        }

        require $view;

        require HN_ROOT . '/app/hn_core/layout/footer.php';
    }

    private function getBooks(): array
    {
        $base = HN_ROOT . '/app/hn_books/books';

        if (!is_dir($base)) {
            return [];
        }

        $dirs = scandir($base);

        if ($dirs === false) {
            return [];
        }

        return array_values(array_filter($dirs, fn($d) =>
            $d !== '.' && $d !== '..' && is_dir($base . '/' . $d)
        ));
    }
}