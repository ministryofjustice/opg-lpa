<?php

declare(strict_types=1);

namespace App\Handler\Initializers;

use Mezzio\Template\TemplateRendererInterface;
use UnexpectedValueException;

/**
 * Getter and Setter, implementing the TemplatingSupportInterface.
 *
 * Class TemplatingSupportTrait
 * @package App\Handler\Initializers
 */
trait TemplatingSupportTrait
{
    /**
     * @var TemplateRendererInterface
     */
    private $render;

    /**
     * @param TemplateRendererInterface $render
     */
    public function setTemplateRenderer(TemplateRendererInterface $render)
    {
        $this->render = $render;
    }

    /**
     * @return TemplateRendererInterface
     */
    public function getTemplateRenderer(): TemplateRendererInterface
    {
        if (!$this->render instanceof TemplateRendererInterface) {
            throw new UnexpectedValueException('TemplateRenderer not set');
        }

        return $this->render;
    }
}
