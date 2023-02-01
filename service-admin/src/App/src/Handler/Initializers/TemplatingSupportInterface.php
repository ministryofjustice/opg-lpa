<?php

namespace App\Handler\Initializers;

use Mezzio\Template\TemplateRendererInterface;

/**
 * Declares handler Middleware support template rendering.
 *
 * Interface TemplatingSupportInterface
 * @package App\Handler\Initializers
 */
interface TemplatingSupportInterface
{
    /**
     * @param TemplateRendererInterface $template
     */
    public function setTemplateRenderer(TemplateRendererInterface $template);

    /**
     * @return ?TemplateRendererInterface
     */
    public function getTemplateRenderer(): ?TemplateRendererInterface;
}
