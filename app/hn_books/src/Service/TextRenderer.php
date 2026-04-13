<?php
declare(strict_types=1);

namespace HnBooks\Service;

final class TextRenderer
{
    public static function render(string $html, string $slug, int $chapter): string
    {
        $prefix = strtoupper(substr($slug, 0, 3));
        $counter = 1;

        return preg_replace_callback('/\[\[LYD\]\]/', function () use ($prefix, $chapter, &$counter) {

            $src = "../audio/{$prefix}-{$chapter}-{$counter}.m4a";
            $counter++;

            return '
<figure class="section-controls">
  <audio controls preload="metadata">
    <source src="' . $src . '" type="audio/mp4">
  </audio>
</figure>';
        }, $html);
    }
}