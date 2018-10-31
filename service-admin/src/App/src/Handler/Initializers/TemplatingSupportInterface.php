<?php

namespace App\Handler\Initializers;

use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Declares Action Middleware support template rendering.
 *
 * Interface TemplatingSupportInterface
 * @package App\Action\Initializers
 */
interface TemplatingSupportInterface
{
    /**
     * @param TemplateRendererInterface $template
     * @return mixed
     */
    public function setTemplateRenderer(TemplateRendererInterface $template);

    /**
     * @return TemplateRendererInterface
     */
    public function getTemplateRenderer() : TemplateRendererInterface;
}
