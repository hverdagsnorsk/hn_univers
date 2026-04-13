<?php
namespace HnCore\Support;

class View
{
    public static function render(string $template, array $data = []): string
    {
        extract($data);

        ob_start();
        require $template;
        return ob_get_clean();
    }
}