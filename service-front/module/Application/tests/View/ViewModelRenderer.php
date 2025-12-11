<?php

declare(strict_types=1);

namespace ApplicationTest\View;

use Laminas\View\Model\ViewModel;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Test helper class to load Twig view templates,
 * stub out any unnecessary TwigFunctions, add other
 * TwigFunctions (if desired), and render ViewModel instances.
 *
 * Typically, you would want one of these for each template you're
 * going to render in a test. Alternatively, you can load different
 * templates with loadTemplate().
 *
 * This will cache the compiled Twig templates to build/twig-cache,
 * which enables them to be included in coverage reports if desired.
 *
 * Example usage:
 *
 * $renderer = new ViewModelRenderer();
 * $renderer->addFunction('moneyFormat', MoneyFormat::class);
 * $renderer->loadTemplate('authenticated/lpa/complete/complete.twig');
 */
class ViewModelRenderer
{
    private Environment $renderer;
    private TemplateWrapper $template;

    public function __construct()
    {
        $this->renderer = new Environment(
            new FilesystemLoader('module/Application/view'),
            ['cache' => 'build/twig-cache']
        );
    }

    /**
     * Load a Twig template from the filesystem. Note that this should be done *after*
     * adding Twig functions with addFunction().
     *
     * @param string $templatePath Path to the Twig template,
     *     relative to module/Application/view/
     */
    public function loadTemplate(string $templatePath): void
    {
        $this->template = $this->renderer->load($templatePath);
    }

    /**
     * Load a Twig template from a string. Note that this should be done *after*
     * adding Twig functions with addFunction().
     *
     * @param string $template Template to load.
     */
    public function loadTemplateString(string $template): void
    {
        $this->template = $this->renderer->createTemplate($template);
    }

    /**
     * Add a TwigFunction to the renderer.
     *
     * @param string $name Name of the Twig function
     * @param mixed $callable Callable object or function; if not supplied,
     *     a no-op function is used by default
     */
    public function addFunction(string $name, mixed $callable = null): void
    {
        if (is_null($callable)) {
            $callable = function (): string {
                return 'noop';
            };
        }

        $this->renderer->addFunction(new TwigFunction($name, $callable));
    }

    /**
     * Add a TwigFunction to the renderer.
     *
     * @param string $name Name of the Twig function
     * @param mixed $callable Callable object or function; if not supplied,
     *     a no-op function is used by default
     */
    public function addFilter(string $name, mixed $callable = null): void
    {
        if (is_null($callable)) {
            $callable = function (): string {
                return 'noop';
            };
        }

        $this->renderer->addFilter(new TwigFilter($name, $callable));
    }

    /**
     * Render the block $block using $viewModel to populate its placeholder
     * variables.
     *
     * @param ViewModel $viewModel ViewModel instance to use for populating
     *     variable placeholders in the template
     * @param ?string $block Block to render within the template; if null, the
     *     whole template is rendered
     */
    public function render(ViewModel $viewModel, ?string $block = null): string
    {
        $vars = (array) $viewModel->getVariables();

        if (is_null($block)) {
            return $this->template->render($vars);
        }

        return $this->template->renderBlock($block, $vars);
    }
}
