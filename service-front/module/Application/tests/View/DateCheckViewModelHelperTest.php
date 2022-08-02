<?php

namespace ApplicationTest\View;

use Laminas\ServiceManager\AbstractPluginManager;
use Application\Form\Lpa\DateCheckForm;
use Application\View\DateCheckViewModelHelper;
use Laminas\View\Model\ViewModel;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class DateCheckViewModelHelperTest extends MockeryTestCase
{
    protected $formElementManager;
    /**
     * @var StorageInterface|ArrayStorage
     */

    private $form;

    /* For the purposes of the test, we extract the variables from the view
     * model and render just the 'content' block of the detailed status page view.
     * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
     * This replicates what happens in ZfcTwig
     * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
     * but ignores the view itself, layout, most filters and functions, view helpers
     * etc. as far as possible.
     */
    private function renderViewModel(?ViewModel $viewModel): string
    {
        $loader = new FilesystemLoader('module/Application/view/application');

        $renderer = new Environment($loader);
        $renderer->addFunction(new TwigFunction('formErrorTextExchange', FormErrorTextExchange::class));

        $template = $renderer->load('authenticated/lpa/date-check/index.twig');

        $vars = (array) $viewModel->getVariables();
        return $template->renderBlock('content', $vars);
    }


    public function testContinuationSheetsViewModelHelperBuild(): void
    {
        $this->form = Mockery::mock(DateCheckForm::class);
        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);

        $lpa = new Lpa([
            'document' => [
                'primaryAttorneys' => [
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human'],
                    ['type' => 'human']
                ]
            ]
        ]);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DateCheckForm', ['lpa' => $lpa]])->andReturn($this->form);


        $viewModel = new ViewModel([
            'form'        => $this->form,
            'returnRoute' => 'lpa/complete',
            'applicants'  => []
        ]);

        $continuationNoteKeys = ContinuationSheetsViewModelHelper::build($lpa);
        $viewModel->setVariables(['continuationNoteKeys' => $continuationNoteKeys]);

        // render and check the HTML
        $html = $this->renderViewModel($viewModel);

        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);
    }
}
