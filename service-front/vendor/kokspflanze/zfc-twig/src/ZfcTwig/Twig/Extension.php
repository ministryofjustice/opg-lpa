<?php

namespace ZfcTwig\Twig;

use Twig\Extension\AbstractExtension;
use ZfcTwig\View\TwigRenderer;

class Extension extends AbstractExtension
{
    /**
     * @var TwigRenderer
     */
    protected $renderer;

    /**
     * @param TwigRenderer $renderer
     */
    public function __construct(TwigRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return TwigRenderer
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return __CLASS__;
    }
}