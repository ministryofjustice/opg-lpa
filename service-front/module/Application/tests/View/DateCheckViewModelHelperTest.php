<?php

namespace ApplicationTest\View;

use Application\Form\Lpa\DateCheckForm;
use Application\View\Helper\FormElementErrorsV2;
use Application\View\Helper\FormErrorTextExchange;
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
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // LPA has more than 4 replacement attorneys (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneys' => [
                        ['type' => 'human', 'dob' => ['date' => '1975-05-10T00:00:00.000000+0000']],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human'],
                        ['type' => 'human']
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // LPA has more than 4 people to notify (generates CS1)
        [
            'lpa' => [
                'document' => [
                    'peopleToNotify' => [[], [], [], [], []]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 1 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],

        // Continuation sheet 2
        // LPA has additional information on how attorneys should act (section 3) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'primaryAttorneyDecisions' => [
                        'howDetails' => 'Attorneys should only step in when I am say so.'
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // LPA has additional information on how replacement attorneys should act (section 4) (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'howDetails' => 'Replacement attorneys should only step in when I say so.'
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // LPA has additional information on when replacement attorneys should act (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'replacementAttorneyDecisions' => [
                        'when' => 'When the primary attorney cannot be contacted'
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
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
            'expectedDonorText' => 'You must have signed and dated continuation sheet/s 2 before you signed section 9
                                   of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // Combined CS1 and CS2
        // 1. LPA has more than 4 people to notify (generates CS1)
        // 2. additional information on when replacement attorneys should act (generates CS2)
        [
            'lpa' => [
                'document' => [
                    'peopleToNotify' => [[], [], [], [], []],
                    'replacementAttorneyDecisions' => [
                        'when' => 'When the primary attorney cannot be contacted'
                    ]
                ]
            ],
            'expectedDonorText' => 'You must have signed and dated continuation sheets 1 and 2 before you signed
                                   section 9 of the LPA, or on the same day.',
            'expectedAttorneyText' => null
        ],
        // Continuation sheet 3
        // Health & welfare LPA - donor cannot sign or make a mark
        [
            'lpa' => [
                'document' => [
                    'type' => 'health-and-welfare',
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => 'This person must have signed continuation sheet 3 on the same day as they sign
                                   section 5 and before the certificate provider signs section 10.',
            'expectedAttorneyText' => null
        ],
        // Property & finance LPA - donor cannot sign or make a mark
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'donor' => [
                        'canSign' => false
                    ]
                ]
            ],
            'expectedDonorText' => 'This person must have signed continuation sheet 3 before the certificate provider
                                   has signed section 10.',
            'expectedAttorneyText' => null
        ],
        // Continuation sheet 4
        // Property & finance LPA - primary attorney is a trust corporation
        [
            'lpa' => [
                'document' => [
                    'type' => 'property-and-financial',
                    'primaryAttorneys' => [
                        ['type' => 'corporation', 'number' => '123']
                    ]
                ]
            ],
            'expectedDonorText' => null,
            'expectedAttorneyText' => 'They must have signed continuation sheet 4 after the \'certificate provider\' has
                                       signed section 10 of the LPA form.'
        ],
    ];

    /* For the purposes of the test, we extract the variables from the view
     * model and render just the 'continuation' block of the date check tool page view.
     * We do this from the Twig template directly, bypassing the ZfcTwig machinery.
     * This replicates what happens in ZfcTwig
     * (see vendor/kokspflanze/zfc-twig/src/View/TwigRenderer.php),
     * but ignores the view itself, layout, most filters and functions, view helpers
     * etc. as far as possible.
     */
    private function setupViewModel(Lpa $lpa, string $templatePath): array
    {
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\DateCheckForm', ['lpa' => $lpa]])->andReturn($this->form);

        $attorneys = $lpa->document->primaryAttorneys;
        if (empty($lpa->document->primaryAttorneys)) {
            $attorneys = $lpa->document->replacementAttorneys;
        }

        $viewModel = new ViewModel([
            'returnRoute' => 'lpa/complete',
            'lpa' => $lpa,
            'attorney' => reset($attorneys)
        ]);
        // using reset() as indexing does not work

        $helperResult = DateCheckViewModelHelper::build($lpa);
        $viewModel->setVariables(['continuationNoteKeys' => $helperResult['continuationNoteKeys'],
                                  'applicants' => []]);

        $loader = new FilesystemLoader('module/Application/view/application');

        $renderer = new Environment($loader);
        $renderer->addFunction(new TwigFunction('formElementErrorsV2', Something::class));
        $renderer->addFunction(new TwigFunction('form', Something::class));
        $renderer->addFunction(new TwigFunction('formErrorTextExchange', Something::class));
        $renderer->addFunction(new TwigFunction('formElement', Something::class));

        $template = $renderer->load($templatePath);

        $vars = (array) $viewModel->getVariables();
        return array($template, $vars);
    }

    private function findHtmlMatches(string $html): array
    {
        $dom = new DOMDocument();

        if (empty(trim($html))) {
            return [];
        }

        $dom->loadHTML($html);
        $xpath = new DOMXpath($dom);
        $matches = $xpath->query("//p[@data-cy='continuation-sheet-info']");

        $matchesArray = [];
        foreach ($matches as $match) {
            array_push($matchesArray, $match->nodeValue);
        }

        return $matchesArray;
    }

    public function testDateCheckViewModelHelperDonorGuidance(): void
    {
        $this->form = Mockery::mock(DateCheckForm::class);

        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);

        foreach ($this->testCases as $index => $testCase) {
            $lpa = new Lpa($testCase['lpa']);
            // render and check the HTML

            [$template, $vars] = $this->setupViewModel($lpa, 'authenticated/lpa/date-check/index.twig');
            $donorGuidanceHtml = $template->renderBlock('donorGuidance', $vars);
            $donorGuidanceMatchesArray = $this->findHtmlMatches($donorGuidanceHtml);

            $expectedText = $testCase['expectedDonorText'];

            echo "\nRunning tests for DateCheckViewModelHelper test case $index\n";
            $this->assertEquals(
                $donorGuidanceMatchesArray,
                str_replace(array("\n", '  '), '', $expectedText ? array($expectedText) : array())
            );
        }
    }

    public function testDateCheckViewModelHelperAttorneyGuidance(): void
    {
        $this->form = Mockery::mock(DateCheckForm::class);

        $this->formElementManager = Mockery::mock(AbstractPluginManager::class);

        foreach ($this->testCases as $index => $testCase) {
            $lpa = new Lpa($testCase['lpa']);

            $x = $this->setupViewModel(
                $lpa,
                'authenticated/lpa/date-check/partials/continuation-note-for-corporation.twig'
            );
            $template = $x[0];
            $vars = $x[1];
            $attorneyGuidanceHtml = $template->renderBlock('attorneyGuidance', $vars);

            $attorneyGuidanceMatchesArray = $this->findHtmlMatches($attorneyGuidanceHtml);

            echo "\nRunning tests for DateCheckViewModelHelper test case $index\n";

            $expectedAttText = $testCase['expectedAttorneyText'];

            $this->assertEquals(
                $attorneyGuidanceMatchesArray,
                str_replace(array("\n", '  '), '', $expectedAttText ? array($expectedAttText) : array())
            );
        }
    }
}
