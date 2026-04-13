<?php
declare(strict_types=1);

namespace HnAdmin\Controller;

use HnAdmin\Service\TextService;

class TextController
{
    public function index(): void
    {
        $service = new TextService();

        $service->handleActions();

        $texts = $service->getAll();

        $view = HN_ADMIN . '/templates/texts_index.php';

        require HN_CORE . '/layout/header.php';
        require $view;
        require HN_CORE . '/layout/footer.php';
    }
}