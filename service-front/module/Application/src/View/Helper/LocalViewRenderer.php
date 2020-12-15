<?php
/**
 * @inheritDoc
 */

namespace Application\View\Helper;

use Twig\Environment as TwigEnvironment;

class LocalViewRenderer implements RendererInterface
{
    /**
     * @param TwigEnvironment $viewRenderer
     */
    public function __construct(TwigEnvironment $viewRenderer)
    {
        $this->viewRenderer = $viewRenderer;
    }

    public function LoadTemplate(string $templateName)
    {
         return $this->viewRenderer->load($templateName)->unwrap();
    }
}
