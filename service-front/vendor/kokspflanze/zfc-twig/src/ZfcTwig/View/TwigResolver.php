<?php

namespace ZfcTwig\View;

use Twig_Environment;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Renderer\RendererInterface as Renderer;

class TwigResolver implements ResolverInterface
{
    /**
     * @var Twig_Environment
     */
    protected $environment;

    /**
     * Constructor.
     *
     * @param Twig_Environment $environment
     */
    public function __construct(Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @inheritDoc
     */
    public function resolve($name, Renderer $renderer = null)
    {
        return $this->environment->loadTemplate($name);
    }
}