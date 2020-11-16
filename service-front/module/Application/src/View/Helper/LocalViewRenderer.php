<?php
/**
 * @inheritDoc
 */

namespace Application\View\Helper;

use Twig\Environment as TwigEnvironment;

class LocalViewRenderer implements RendererInterface
{
     // this wraps TwigWrapper because TwigWrapper cannot be mocked due to being marked final
     // in other words some acrobatics to get around incompatibility between Twig and Mockery

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
