<?php
/**
 * This enables the isolating of TwigWrapper because TwigWrapper cannot be mocked due to being marked final.
 * In other words some acrobatics to get around incompatibility between Twig and Mockery
 */

namespace Application\View\Helper;

interface RendererInterface
{
/**
 * Load the twig template
 *
 * @param string $templateName
 */
    public function LoadTemplate(string $templateName);
}
