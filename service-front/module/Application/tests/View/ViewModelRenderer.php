<?php

namespace ApplicationTest\View;

use Laminas\View\Model\ViewModel;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TwigFunction;

function noop()
{
    return 'Noop';
}

/**
 * Test helper class to load Twig view templates,
 * stub out any unnecessary TwigFunctions, add other
 * TwigFunctions (if desired), and render ViewModel instances.
 *
 * Typically, you would want one of these for each template you're
 * going to render in a test.
 *
 * This will cache the compiled Twig templates to build/twig-cache,
 * which enables them to be included in coverage reports if desired.
 */
class ViewModelRenderer
{
    /** @var Environment */
    private $renderer;

    /** @var Template */
    private $template;

    /**
     * @param string $templatePath Path to the template to be rendered
     */
    public function __construct(string $templatePath)
    {
        $this->renderer = new Environment(
            new FilesystemLoader('module/Application/view'),
            ['cache' => 'build/twig-cache']
        );

        $this->template = $this->renderer->load($templatePath);
    }

    /**
     * Add a TwigFunction to the renderer
     *
     * @param string $name Name of the Twig function
     * @param mixed $callable Callable object or function; if not supplied,
     *     a no-op function is used by default
     */
    public function addFunction(string $name, mixed $callable = noop): void
    {
        $this->renderer->addFunction(new TwigFunction($name, $callable));
    }

    /**
     * Render the block $block using $viewModel to populate its placeholder
     * variables.
     *
     * @param string $block Block to render within the template
     * @param ViewModel $viewModel ViewModel instance to use for populating
     *     variable placeholders in the template
     */
    public function render(string $block, ViewModel $viewModel): string
    {
        $vars = (array) $viewModel->getVariables();
        return $this->template->renderBlock($block, $vars);
    }
}
