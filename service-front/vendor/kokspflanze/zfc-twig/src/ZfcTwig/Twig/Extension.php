<?php

namespace ZfcTwig\Twig;

use Twig_Extension;
use ZfcTwig\View\TwigRenderer;

class Extension extends Twig_Extension
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