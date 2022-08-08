<?php

namespace ApplicationTest\View;

use Application\Form\Lpa\DateCheckForm;
use Application\View\DateCheckViewModelHelper;
use DOMDocument;
use DOMXpath;
use Laminas\ServiceManager\AbstractPluginManager;
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

    private $testCases = [
        // Continuation sheet 1
        // LPA has more than 4 primary attorneys (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human']
                    ]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                               of the LPA, or on the same day.'
        ],
        // LPA has more than 4 replacement attorneys (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneys' => [
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human']
                    ]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                               of the LPA, or on the same day.'
        ],
        // LPA has more than 4 people to notify (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'peopleToNotify' => [[], [], [], [], []]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                               of the LPA, or on the same day.'
        ],

        // Continuation sheet 2
        // LPA has additional information on how attorneys should act? (section 3) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneyDecisions' => [
                        'howDetails' => 'Attorneys should only step in when I am say so.'
                    ]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                               of the LPA, or on the same day.'
        ],
        // LPA has additional information on how replacement attorneys should act? (section 4) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                               of the LPA, or on the same day.'
        ],
        // LPA has additional information on when replacement attorneys should act? (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'when' => 'When the primary attorney cannot be contacted'
                    ]
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                               of the LPA, or on the same day.'
        ],
        // LPA has additional information in preferences and instructions (section 7) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'instruction' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor
                                      incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud
                                      exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute
                                      irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat
                                      nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa
                                      qui officia deserunt mollit anim id est laborum.'
                ]
            ],
            'expectedText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                               of the LPA, or on the same day.'
        ]
    ];

    /* For the purposes of the test, we extract the variables from the view
     * model and render just the 'continuation' block of the date check tool page view.
     * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
     * This replicates what happens in ZfcTwig
     * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
     * but ignores the view itself, layout, most filters and functions, view helpers
     * etc. as far as possible.
     */
    private function renderViewModel(Lpa $lpa): string
    {
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DateCheckForm', ['lpa' => $lpa]])->andReturn($this->form);

        $viewModel = new ViewModel([
            'form'        => $this->form,
            'returnRoute' => 'lpa/complete',
        ]);

        $helperResult = DateCheckViewModelHelper::build($lpa);
        $viewModel->setVariables(['continuationNoteKeys' => $helperResult['continuationNoteKeys'],
                                  'applicants' => []]);

        $loader = new FilesystemLoader('module/Application/view/application');

        $renderer = new Environment($loader);
        $renderer->addFunction(new TwigFunction('formErrorTextExchange', FormErrorTextExchange::class));
        $renderer->addFunction(new TwigFunction('form', Form::class));
        $renderer->addFunction(new TwigFunction('formElement', Form::class));
        $renderer->addFunction(new TwigFunction('formElementErrorsV2', Form::class));

        $template = $renderer->load('authenticated/lpa/date-check/index.twig');

        $vars = (array) $viewModel->getVariables();
        return $template->renderBlock('continuation', $vars);
    }


    public function testDateCheckViewModelHelperBuild(): void
    {
        $this->form = Mockery::mock(DateCheckForm::class);
        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);

        foreach ($this->testCases as $index => $testCase) {
            $lpa = new Lpa($testCase['lpa']);
            // render and check the HTML
            $html = $this->renderViewModel($lpa);

            $dom = new DOMDocument();
            $dom->loadHTML($html);
            $xpath = new DOMXpath($dom);
            $matches = $xpath->query("//p[@data-cy='continuation-sheet-info']");

            echo "\nRunning tests for DateCheckViewModelHelper test case $index\n";
            $this->assertEquals(
                $matches[0]->nodeValue,
                str_replace(array("\n", '  '), '', $testCase['expectedText'])
            );
        }
    }
}
