<?php

/**
 * @inheritDoc
 */

namespace Application\View\Helper;

use Twig\Environment as TwigEnvironment;

class LocalViewRenderer
{
    /**
     * @param TwigEnvironment $viewRenderer
     */
    public function __construct(TwigEnvironment $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;
    }

    /**
     * @return string
     */
    public function renderTemplate(string $templateName, array $data)
    {
        $template = $this->viewRenderer->load($templateName)->unwrap();
        return $template->render($data);
    }
}
