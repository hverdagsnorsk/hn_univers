<?php
declare(strict_types=1);

namespace HnCore\Assets;

class AssetLoader
{
    private array $css = [];
    private array $js  = [];

    public function __construct()
    {
        /* -------------------------
           GLOBAL CSS
        ------------------------- */
        $this->css[] = '/assets/css/base.css';
        $this->css[] = '/assets/css/layout.css';
    }

    /* =====================================================
       ENABLE MODULES (CSS + JS)
    ===================================================== */

    public function enableAdmin(): void
    {
        $this->css[] = '/assets/css/admin.css';

        $this->js[] = [
            'src'    => '/assets/js/admin.js',
            'module' => true
        ];
    }

    public function enableLex(): void
    {
        $this->css[] = '/assets/css/lex.css';

        $this->js[] = [
            'src'    => '/assets/js/lex.js',
            'module' => true
        ];
    }

    public function enableCourse(): void
    {
        $this->css[] = '/assets/css/course.css';

        $this->js[] = [
            'src'    => '/assets/js/course.js',
            'module' => true
        ];
    }

    public function enableReader(): void
    {
        $this->css[] = '/assets/css/reader.css';

        $this->js[] = [
            'src'    => '/assets/js/reader.js',
            'module' => true
        ];
    }

    /* =====================================================
       RENDER CSS
    ===================================================== */

    public function renderCss(): string
    {
        $out = '';

        foreach (array_unique($this->css) as $href) {
            $out .= '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">' . PHP_EOL;
        }

        return $out;
    }

    /* =====================================================
       RENDER JS
    ===================================================== */

    public function renderJs(): string
    {
        $out = '';

        foreach ($this->js as $script) {

            $src = htmlspecialchars($script['src'] ?? '');

            if (!$src) {
                continue;
            }

            $isModule = !empty($script['module']);

            if ($isModule) {
                $out .= '<script type="module" src="' . $src . '"></script>' . PHP_EOL;
            } else {
                $out .= '<script src="' . $src . '" defer></script>' . PHP_EOL;
            }
        }

        return $out;
    }
}