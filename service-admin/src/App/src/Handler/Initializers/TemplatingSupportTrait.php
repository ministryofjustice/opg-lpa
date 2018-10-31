<?php

declare(strict_types=1);

namespace App\Handler\Initializers;

use Zend\Expressive\Template\TemplateRendererInterface;
use UnexpectedValueException;

/**
 * Getter and Setter, implementing the TemplatingSupportInterface.
 *
 * Class TemplatingSupportTrait
 * @package App\Action\Initializers
 */
trait TemplatingSupportTrait
{
    /**
     * @var TemplateRendererInterface
     */
    private $template;

    /**
     * @param TemplateRendererInterface $template
     */
    public function setTemplateRenderer(TemplateRendererInterface $template)
    {
        $this->template = $template;
    }

    /**
     * @return TemplateRendererInterface
     */
    public function getTemplateRenderer() : TemplateRendererInterface
    {

        if (!( $this->template instanceof TemplateRendererInterface )) {
            throw new UnexpectedValueException('TemplateRenderer not set');
        }

        return $this->template;
    }
}
